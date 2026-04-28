<?php
/**
 * Normalize foto_url values from the database for public URLs.
 *
 * Rows sometimes store only the bare filename (e.g. roster-abc123.jpg). The site
 * serves files from assets/uploads/, so bare names must be prefixed or the browser
 * requests /roster-abc123.jpg and gets 404.
 */
declare(strict_types=1);

if (!function_exists('vcf_normalize_foto_url')) {
    function vcf_normalize_foto_url(?string $foto_url): string
    {
        $foto_url = trim((string) $foto_url);
        if ($foto_url === '') {
            return '';
        }
        if (preg_match('#^(https?:)?//#i', $foto_url)) {
            return $foto_url;
        }
        $foto_url = ltrim($foto_url, '/');
        if (str_starts_with($foto_url, 'assets/uploads/')) {
            return $foto_url;
        }
        if (strpos($foto_url, '/') === false && preg_match('/\.(jpe?g|png|webp)$/i', $foto_url)) {
            return 'assets/uploads/' . $foto_url;
        }
        return $foto_url;
    }
}
