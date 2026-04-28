<?php
/**
 * Admin: wipe the public page cache.
 *
 * The public site caches rendered HTML for 60 seconds to relieve pressure
 * on the shared MySQL host. After saving content (hero, roster, MOTM,
 * donations, etc.) admins can hit this endpoint to flush the cache and
 * see their changes immediately instead of waiting up to a minute.
 */
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../includes/page_cache.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

if (!csrf_verify()) {
    header('Location: dashboard.php?cache_err=csrf');
    exit;
}

$count = vcf_page_cache_clear();
admin_log('clear_page_cache', sprintf('Removed %d cached pages.', $count));
header('Location: dashboard.php?cache_cleared=' . (int) $count);
exit;
