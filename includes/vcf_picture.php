<?php
/**
 * <picture> helper with automatic WebP fallback.
 *
 * Emits a <picture> with a WebP <source> ONLY when a sibling .webp file exists
 * on disk next to the jpg/png; otherwise a plain <img>. This makes WebP a
 * progressive enhancement: deploy the helper now, and as .webp files appear
 * (committed for static assets, or generated on the server by
 * scripts/generate-webp.php for uploads) the lighter format "lights up"
 * automatically with zero template changes and no risk of broken images.
 *
 * Pair with `picture { display: contents; }` (in style.css) so the wrapper is
 * layout-neutral and existing `.some-image img` CSS keeps matching.
 */
declare(strict_types=1);

if (!function_exists('vcf_webp_sibling')) {
    /**
     * Return the .webp sibling URL for a local jpg/png image when that file
     * exists on disk, else null. Absolute (http/protocol-relative/data) URLs
     * and non jpg/png are skipped.
     */
    function vcf_webp_sibling(string $url): ?string
    {
        if ($url === '' || preg_match('#^(https?:)?//#i', $url) || stripos($url, 'data:') === 0) {
            return null;
        }
        if (!preg_match('#\.(jpe?g|png)$#i', $url)) {
            return null;
        }
        $webpUrl = preg_replace('#\.(jpe?g|png)$#i', '.webp', $url);

        // Map the public URL to a filesystem path by anchoring on /assets/.
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $url;
        }
        $pos = strpos($path, 'assets/');
        if ($pos === false) {
            return null;
        }
        $relWebp = preg_replace('#\.(jpe?g|png)$#i', '.webp', substr($path, $pos));
        $fs = dirname(__DIR__) . '/' . ltrim((string) $relWebp, '/');

        return is_file($fs) ? $webpUrl : null;
    }
}

if (!function_exists('vcf_picture')) {
    /**
     * @param string $src  jpg/png URL (already resolved with any base prefix).
     * @param string $alt  Alt text (will be escaped).
     * @param array  $opts class, loading ('lazy'|'eager'), decoding, width, height.
     */
    function vcf_picture(string $src, string $alt, array $opts = []): string
    {
        $attrs = 'src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '"';
        if (!empty($opts['class'])) {
            $attrs .= ' class="' . htmlspecialchars((string) $opts['class']) . '"';
        }
        $attrs .= ' loading="' . htmlspecialchars((string) ($opts['loading'] ?? 'lazy')) . '"';
        $attrs .= ' decoding="' . htmlspecialchars((string) ($opts['decoding'] ?? 'async')) . '"';
        if (isset($opts['width'])) {
            $attrs .= ' width="' . (int) $opts['width'] . '"';
        }
        if (isset($opts['height'])) {
            $attrs .= ' height="' . (int) $opts['height'] . '"';
        }

        $webp = vcf_webp_sibling($src);
        if ($webp === null) {
            return '<img ' . $attrs . '>';
        }
        return '<picture><source type="image/webp" srcset="' . htmlspecialchars($webp) . '"><img ' . $attrs . '></picture>';
    }
}
