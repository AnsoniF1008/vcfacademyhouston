<?php
/**
 * Admin: wipe public page HTML cache and API JSON cache (roster-player, etc.).
 *
 * TTLs on the public site reduce MySQL load; this button flushes immediately.
 * POST saves also auto-invalidate (see admin/includes/auth.php).
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

$count = vcf_public_cache_clear();
admin_log('clear_public_cache', sprintf('Removed %d cached page/API files.', $count));
header('Location: dashboard.php?cache_cleared=' . (int) $count);
exit;
