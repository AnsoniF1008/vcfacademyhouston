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

/**
 * Optimize an uploaded image: resize down to `maxDim` on the longest edge
 * (if larger) and re-encode as WebP at `quality` (0-100). On success the
 * source file is deleted and the new .webp file is returned (server path).
 * On any failure (GD missing, decode error, save error) returns null and
 * leaves the original file untouched — caller can fall back to it.
 *
 * Typical savings on uploads: 3-10x smaller bytes for the same visual
 * quality, on top of dimensions cropped to a reasonable maximum.
 *
 * @param string $sourcePath Absolute filesystem path to the source image.
 * @param int    $maxDim     Longest edge in pixels (default 1200).
 * @param int    $quality    WebP quality 1-100 (default 82, visually lossless).
 * @return string|null Absolute path of the new .webp, or null on failure.
 */
function vcf_optimize_to_webp(string $sourcePath, int $maxDim = 1200, int $quality = 82): ?string {
    if (!is_file($sourcePath)) return null;
    if (!function_exists('imagewebp') || !function_exists('imagecreatefromjpeg')) return null;

    $info = @getimagesize($sourcePath);
    if (!$info || empty($info['mime'])) return null;
    $mime = $info['mime'];

    // Load source according to its real type (no guessing from extension).
    $src = null;
    switch ($mime) {
        case 'image/jpeg':
            $src = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $src = @imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            // Already webp — only re-encode if it's noticeably larger than
            // the quality threshold would produce. Otherwise no-op (keep as is).
            $src = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return null;
    }
    if (!$src) return null;

    $w = imagesx($src);
    $h = imagesy($src);
    $longest = max($w, $h);
    if ($longest > $maxDim) {
        $ratio = $maxDim / $longest;
        $newW = (int) round($w * $ratio);
        $newH = (int) round($h * $ratio);
        $resized = imagecreatetruecolor($newW, $newH);
        // Preserve PNG/WebP transparency.
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $newW, $newH, $transparent);
        imagecopyresampled($resized, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($src);
        $src = $resized;
    }

    $destPath = preg_replace('/\.(jpe?g|png|webp)$/i', '', $sourcePath) . '.webp';
    $ok = @imagewebp($src, $destPath, max(1, min(100, $quality)));
    imagedestroy($src);
    if (!$ok || !is_file($destPath)) {
        return null;
    }

    // Only swap if the .webp is actually a wire-size win. PNG screenshots of
    // text can occasionally end up larger as webp; in that case keep the
    // original.
    $originalSize = @filesize($sourcePath) ?: 0;
    $newSize = @filesize($destPath) ?: 0;
    if ($newSize > 0 && $newSize < $originalSize && $sourcePath !== $destPath) {
        @unlink($sourcePath);
        return $destPath;
    }

    // Re-encode didn't help — discard the .webp, keep the original.
    if ($sourcePath !== $destPath) {
        @unlink($destPath);
    }
    return null;
}
