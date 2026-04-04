<?php
/**
 * Upload helper — safe deletion of previously uploaded files.
 *
 * Usage: delete_upload('assets/uploads/roster-abc123.jpg');
 *
 * Guards against path-traversal: only removes files whose resolved path
 * lives inside the project's assets/uploads/ directory.
 */
function delete_upload(string $relPath): void {
    if ($relPath === '') return;

    // Project root is two levels above this file  (/admin/includes/ → /)
    $webRoot    = dirname(__DIR__, 2);
    $uploadsDir = realpath($webRoot . '/assets/uploads');
    if ($uploadsDir === false) return;

    // Resolve the target without allowing '..' traversal
    $candidate = $webRoot . DIRECTORY_SEPARATOR
        . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relPath), DIRECTORY_SEPARATOR);

    $target = realpath($candidate);
    if ($target === false) return;  // File doesn't exist — nothing to delete

    // Strict prefix check: target MUST be inside uploads/
    if (strpos($target, $uploadsDir . DIRECTORY_SEPARATOR) !== 0) return;

    if (is_file($target)) {
        @unlink($target);
    }
}
