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

if (!function_exists('vcf_rate_limit_file')) {
    /** Resolve the on-disk JSON path for a (bucket, client) pair. */
    function vcf_rate_limit_file(string $bucket, string $clientId): string
    {
        $bucket = preg_replace('/[^a-z0-9_-]/i', '', $bucket) ?: 'default';
        $hash = sha1($bucket . '|' . $clientId);
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vcf_rl';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        return $dir . DIRECTORY_SEPARATOR . $hash . '.json';
    }
}

if (!function_exists('vcf_rate_limit_hits')) {
    /**
     * Read-only: return the recorded hit timestamps still inside the window,
     * sorted ascending. Unlike vcf_rate_limit_check() this does NOT record a
     * new hit — use it to *inspect* a counter (e.g. "is this IP locked out?")
     * without consuming an attempt.
     *
     * @return int[] timestamps within the window (oldest first)
     */
    function vcf_rate_limit_hits(string $bucket, string $clientId, int $windowSeconds): array
    {
        $file = vcf_rate_limit_file($bucket, $clientId);
        if (!is_file($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        $windowStart = time() - $windowSeconds;
        $hits = [];
        foreach ($decoded as $ts) {
            $ts = (int) $ts;
            if ($ts >= $windowStart) {
                $hits[] = $ts;
            }
        }
        sort($hits);
        return $hits;
    }
}

if (!function_exists('vcf_rate_limit_record')) {
    /**
     * Append a single hit for a (bucket, client) pair. Pair with
     * vcf_rate_limit_hits() to build a "record-on-failure" throttle (e.g.
     * count only failed logins, not every page view of the login form).
     */
    function vcf_rate_limit_record(string $bucket, string $clientId, int $windowSeconds = 3600): void
    {
        $file = vcf_rate_limit_file($bucket, $clientId);
        $hits = vcf_rate_limit_hits($bucket, $clientId, $windowSeconds);
        $hits[] = time();
        @file_put_contents($file, json_encode($hits), LOCK_EX);
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
