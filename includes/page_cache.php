<?php
/**
 * Lightweight file-based page cache for public PHP pages.
 *
 * Goal: drastically reduce MySQL connections on shared hosting (Hostinger
 * caps the live plan at 500 connections / hour per MySQL user). For
 * anonymous visitors public pages render the same HTML for everyone for
 * a short window, so caching the rendered output for ~60 seconds turns
 * hundreds of requests into a single DB hit.
 *
 * Bypass conditions (cache is NOT served / NOT written):
 *   - Method != GET
 *   - PHPSESSID cookie present (admin or any session-bound visitor)
 *   - motm_voted_* / star_voted_* cookies present (visitor has a vote
 *     state we cannot share with strangers)
 *
 * Storage: cache/page/<sha1>.html (within the project, blocked by
 * .htaccess from direct access). Ephemeral; can be safely deleted.
 *
 * Typical use in a public page (BEFORE requiring database.php):
 *
 *   require __DIR__ . '/includes/page_cache.php';
 *   if (vcf_page_cache_try_serve(60)) { exit; }
 *   require __DIR__ . '/config/database.php';
 *   vcf_page_cache_start(60);
 *   // … rest of the page …
 *
 * Stale-if-error: if MySQL is rate-limited or down, database.php can call
 * vcf_page_cache_try_serve_stale(3600) to fall back to the last good
 * snapshot for up to one hour instead of showing a hard error page.
 */

declare(strict_types=1);

if (!function_exists('vcf_page_cache_dir')) {
    function vcf_page_cache_dir(): string
    {
        $dir = __DIR__ . '/../cache/page';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }
}

if (!function_exists('vcf_page_cache_should_skip')) {
    function vcf_page_cache_should_skip(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return true;
        }
        foreach (array_keys($_COOKIE ?? []) as $name) {
            $n = (string) $name;
            // Any active session (admin or otherwise) gets a fresh render.
            if (stripos($n, 'PHPSESSID') !== false) {
                return true;
            }
            // Visitors that already cast a vote see a personalised state
            // (their nominee's vote count, "you already voted", etc.).
            if (strpos($n, 'motm_voted_') === 0 || strpos($n, 'star_voted_') === 0) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('vcf_page_cache_key')) {
    function vcf_page_cache_key(): string
    {
        // Use the full URI (path + query string) so pages like
        // match.php?id=8 cache as a separate entry from match.php?id=9
        // and from the canonical match.php (no query).
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        return sha1($host . '|' . $uri);
    }
}

if (!function_exists('vcf_page_cache_try_serve')) {
    /**
     * If a fresh cached copy exists for this URL, send it and return true.
     * Caller should `exit;` when this returns true.
     */
    function vcf_page_cache_try_serve(int $ttl = 60): bool
    {
        if (vcf_page_cache_should_skip()) {
            return false;
        }
        $file = vcf_page_cache_dir() . '/' . vcf_page_cache_key() . '.html';
        if (!is_file($file)) {
            return false;
        }
        $age = time() - (int) @filemtime($file);
        if ($age < 0 || $age > $ttl) {
            return false;
        }
        $body = @file_get_contents($file);
        if ($body === false || $body === '') {
            return false;
        }
        header('X-Cache: HIT');
        header('X-Cache-Age: ' . $age);
        header('Content-Type: text/html; charset=utf-8');
        echo $body;
        return true;
    }
}

if (!function_exists('vcf_page_cache_try_serve_stale')) {
    /**
     * Last-resort cache: serve a previously stored copy even if it is
     * past its TTL. Intended for catastrophic situations (MySQL down,
     * rate-limit exhausted) — better to show stale data than a broken
     * error page.
     *
     * Caller should `exit;` when this returns true.
     */
    function vcf_page_cache_try_serve_stale(int $maxAge = 3600): bool
    {
        if (vcf_page_cache_should_skip()) {
            return false;
        }
        $file = vcf_page_cache_dir() . '/' . vcf_page_cache_key() . '.html';
        if (!is_file($file)) {
            return false;
        }
        $age = time() - (int) @filemtime($file);
        if ($age < 0 || $age > $maxAge) {
            return false;
        }
        $body = @file_get_contents($file);
        if ($body === false || $body === '') {
            return false;
        }
        header('X-Cache: STALE');
        header('X-Cache-Age: ' . $age);
        header('Content-Type: text/html; charset=utf-8');
        echo $body;
        return true;
    }
}

if (!function_exists('vcf_page_cache_start')) {
    /**
     * Capture this request's output and write it to the cache when the page
     * finishes rendering. No-op if the request should not be cached.
     */
    function vcf_page_cache_start(int $ttl = 60): void
    {
        if (vcf_page_cache_should_skip()) {
            header('X-Cache: SKIP');
            return;
        }
        header('X-Cache: MISS');
        $key = vcf_page_cache_key();
        ob_start(function (string $body) use ($key): string {
            // Don't cache empty / suspiciously tiny bodies (likely an error).
            if ($body === '' || strlen($body) < 256) {
                return $body;
            }
            // Don't cache server errors. 4xx (incl. 404) is intentional
            // page state and worth caching.
            $code = function_exists('http_response_code') ? (int) http_response_code() : 200;
            if ($code >= 500) {
                return $body;
            }
            $file = vcf_page_cache_dir() . '/' . $key . '.html';
            $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
            if (@file_put_contents($tmp, $body, LOCK_EX) !== false) {
                @rename($tmp, $file);
            } else {
                @unlink($tmp);
            }
            return $body;
        });
        unset($ttl);
    }
}

if (!function_exists('vcf_page_cache_clear')) {
    /**
     * Wipe every cached page. Intended for the admin "clear cache" button.
     */
    function vcf_page_cache_clear(): int
    {
        $dir = vcf_page_cache_dir();
        $count = 0;
        $files = glob($dir . '/*.html') ?: [];
        foreach ($files as $f) {
            if (@unlink($f)) {
                $count++;
            }
        }
        $tmps = glob($dir . '/*.tmp') ?: [];
        foreach ($tmps as $f) {
            @unlink($f);
        }
        return $count;
    }
}
