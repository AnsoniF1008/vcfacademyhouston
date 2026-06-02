<?php
/**
 * Lightweight file-based rate limiter.
 * Usage:
 *   require __DIR__ . '/../includes/rate_limit.php';
 *   if (!vcf_rate_limit_check('motm-vote', $ip, 5, 60)) {
 *       http_response_code(429);
 *       header('Retry-After: 60');
 *       echo json_encode(['error' => 'rate_limited']);
 *       exit;
 *   }
 *
 * Storage: writes a small JSON per (key, bucket) under sys_get_temp_dir().
 * No external dependencies, no APCu/Redis required, safe on shared hosting.
 *
 * Best-effort: occasional race conditions could let 1-2 extra requests slip through —
 * acceptable for spam control where the goal is "don't accept 1000 votes/sec".
 */
declare(strict_types=1);

if (!function_exists('vcf_rate_limit_check')) {
    /**
     * @param string $bucket   Logical name (e.g. "motm-vote", "star-vote")
     * @param string $clientId Identity (usually the IP)
     * @param int    $maxHits  Maximum hits allowed within the window
     * @param int    $windowSeconds Window length in seconds
     * @return bool true if allowed, false if rate-limited
     */
    function vcf_rate_limit_check(string $bucket, string $clientId, int $maxHits = 10, int $windowSeconds = 60): bool
    {
        $bucket = preg_replace('/[^a-z0-9_-]/i', '', $bucket) ?: 'default';
        $hash = sha1($bucket . '|' . $clientId);
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vcf_rl';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $file = $dir . DIRECTORY_SEPARATOR . $hash . '.json';

        $now = time();
        $windowStart = $now - $windowSeconds;
        $hits = [];

        if (is_file($file)) {
            $raw = @file_get_contents($file);
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $ts) {
                        $ts = (int) $ts;
                        if ($ts >= $windowStart) {
                            $hits[] = $ts;
                        }
                    }
                }
            }
        }

        if (count($hits) >= $maxHits) {
            return false;
        }

        $hits[] = $now;
        @file_put_contents($file, json_encode($hits), LOCK_EX);

        // Opportunistic cleanup: 1% chance per call.
        if (mt_rand(1, 100) === 1) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $oldFile) {
                $mtime = @filemtime($oldFile);
                if ($mtime !== false && $mtime < ($now - 86400)) {
                    @unlink($oldFile);
                }
            }
        }

        return true;
    }
}

if (!function_exists('vcf_client_ip')) {
    /**
     * Best-effort client IP for rate-limiting / dedup. Honours the first
     * X-Forwarded-For hop (Hostinger/Cloudflare sit in front of PHP) and
     * falls back to REMOTE_ADDR. Capped at 45 chars (IPv6 max) and never
     * empty so it is always usable as a rate-limit identity.
     */
    function vcf_client_ip(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        if (strlen($ip) > 45) {
            $ip = substr($ip, 0, 45);
        }
        return $ip !== '' ? $ip : '0.0.0.0';
    }
}
