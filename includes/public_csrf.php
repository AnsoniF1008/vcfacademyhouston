<?php
/**
 * Stateless CSRF protection for PUBLIC, session-less, cached pages.
 *
 * The admin panel uses synchronizer tokens (admin/includes/csrf.php), but that
 * relies on a server session. Public pages must stay session-less: the moment a
 * PHPSESSID cookie exists, includes/page_cache.php skips the cache for every
 * request that visitor makes (see vcf_page_cache_should_skip / header.php), and
 * a per-visitor token can't live inside a cached HTML body anyway. So a classic
 * token is architecturally off the table here.
 *
 * Instead we verify the request actually originated from our own site — the
 * approach OWASP recommends when tokens aren't feasible ("Verifying Origin With
 * Standard Headers"). A cross-site forged POST is sent by the victim's browser
 * with the attacker page's Origin (or Referer), which will not match our host,
 * so we reject it. This is stateless, cache-safe and works without JavaScript.
 * It complements the honeypot, the per-IP rate limit, and the CSP
 * `form-action 'self'` directive already in .htaccess.
 */
declare(strict_types=1);

if (!function_exists('vcf_verify_same_origin')) {
    /**
     * @return bool true if the request looks same-origin (or is inconclusive,
     *              in which case the honeypot + rate limit remain as backstops);
     *              false only when an Origin/Referer is present AND its host
     *              does not match ours — i.e. a genuine cross-site POST.
     */
    function vcf_verify_same_origin(): bool
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        if ($host === '') {
            // We can't establish our own host; don't block legitimate traffic.
            return true;
        }

        $hostMatches = static function (?string $url) use ($host): ?bool {
            if (!is_string($url) || $url === '') {
                return null; // Header absent → inconclusive.
            }
            $h = parse_url($url, PHP_URL_HOST);
            if (!is_string($h) || $h === '') {
                return null;
            }
            return strcasecmp($h, $host) === 0;
        };

        // Origin is the most reliable signal; modern browsers send it on POST.
        $byOrigin = $hostMatches($_SERVER['HTTP_ORIGIN'] ?? null);
        if ($byOrigin !== null) {
            return $byOrigin;
        }

        // Fall back to the Referer host when Origin is missing.
        $byReferer = $hostMatches($_SERVER['HTTP_REFERER'] ?? null);
        if ($byReferer !== null) {
            return $byReferer;
        }

        // Neither header present (e.g. privacy tools strip them): inconclusive.
        // A real cross-site attack would carry an Origin, so allow and rely on
        // the honeypot + rate limit rather than break stripped-but-legit posts.
        return true;
    }
}
