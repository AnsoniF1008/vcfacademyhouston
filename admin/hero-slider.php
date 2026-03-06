<?php
require __DIR__ . '/includes/auth.php';
require_permission('hero_slider');
require __DIR__ . '/includes/csrf.php';
require __DIR__ . '/../config/database.php';

$uploadDir = __DIR__ . '/../assets/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB
$heroMaxWidth = 1920;
$heroJpegQuality = 82;

/**
 * Redimensiona y comprime imagen del hero para mejorar LCP (max 1920px ancho, calidad 82).
 */
function optimize_hero_image(string $path, int $maxWidth = 1920, int $jpegQuality = 82): bool {
    if (!function_exists('imagecreatefromjpeg')) {
        return true;
    }
    $info = @getimagesize($path);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return true;
    }
    $w = (int) $info[0];
    $h = (int) $info[1];
    if ($w <= $maxWidth && $info[2] === IMAGETYPE_JPEG && filesize($path) < 350 * 1024) {
        return true;
    }
    $src = null;
    if ($info[2] === IMAGETYPE_JPEG) {
        $src = @imagecreatefromjpeg($path);
    } elseif ($info[2] === IMAGETYPE_PNG) {
        $src = @imagecreatefrompng($path);
    } elseif ($info[2] === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
        $src = @imagecreatefromwebp($path);
    }
    if (!$src) {
        return true;
    }
    $newW = min($w, $maxWidth);
    $newH = (int) round($h * ($newW / $w));
    $dst = imagecreatetruecolor($newW, $newH);
    if (!$dst) {
        imagedestroy($src);
        return true;
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagedestroy($src);
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $ok = false;
    if ($ext === 'png') {
        $ok = imagepng($dst, $path, 8);
    } elseif ($ext === 'webp' && function_exists('imagewebp')) {
        $ok = imagewebp($dst, $path, 82);
    } else {
        $ok = imagejpeg($dst, $path, $jpegQuality);
    }
    imagedestroy($dst);
    return $ok;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'danger';
    } elseif (isset($_POST['delete_id'])) {
        $id = (int) $_POST['delete_id'];
        $pdo->prepare("DELETE FROM hero_slides WHERE id = ?")->execute([$id]);
        $message = 'Slide deleted.';
        $messageType = 'success';
    } else {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $button_text = trim($_POST['button_text'] ?? '') ?: 'Read More';
        $button_url = trim($_POST['button_url'] ?? '') ?: null;
        $orden = (int) ($_POST['orden'] ?? 0);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $image_url = null;

        if ($title === '') {
            $message = 'Title is required.';
            $messageType = 'danger';
        } elseif ($id === 0 && empty($_FILES['image']['name'])) {
            $message = 'Image is required for new slide. Recommended: 1920×1080px.';
            $messageType = 'danger';
        } else {
            if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mime, $allowedTypes, true)) {
                    $message = 'Invalid image type. Use JPG, PNG or WebP.';
                    $messageType = 'danger';
                } elseif ($_FILES['image']['size'] > $maxSize) {
                    $message = 'Image too large. Max 5MB.';
                    $messageType = 'danger';
                } else {
                    $clientName = basename($_FILES['image']['name'] ?? '');
                    if (strpos($clientName, '..') !== false || preg_match('/\.(php|phtml|php3|php4|php5|phar|htaccess)(\.|$)/i', $clientName)) {
                        $message = 'Invalid file name.';
                        $messageType = 'danger';
                    } else {
                    $ext = match ($mime) {
                        'image/jpeg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        default => 'jpg',
                    };
                    $ext = in_array($ext, ['jpg', 'png', 'webp'], true) ? $ext : 'jpg';
                    $filename = 'hero-' . uniqid() . '.' . $ext;
                    if (strpos($filename, '..') !== false || strpbrk($filename, '/\\') !== false) {
                        $message = 'Invalid file name.';
                        $messageType = 'danger';
                    } else {
                    $fullPath = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $fullPath)) {
                        optimize_hero_image($fullPath, $heroMaxWidth, $heroJpegQuality);
                        $image_url = 'assets/uploads/' . $filename;
                    }
                    }
                    }
                }
            }

            if ($message === '') {
                if ($id > 0) {
                    if ($image_url !== null) {
                        $pdo->prepare("UPDATE hero_slides SET image_url = ?, title = ?, button_text = ?, button_url = ?, orden = ?, activo = ? WHERE id = ?")
                            ->execute([$image_url, $title, $button_text, $button_url, $orden, $activo, $id]);
                    } else {
                        $pdo->prepare("UPDATE hero_slides SET title = ?, button_text = ?, button_url = ?, orden = ?, activo = ? WHERE id = ?")
                            ->execute([$title, $button_text, $button_url, $orden, $activo, $id]);
                    }
                    $message = 'Slide updated.';
                } else {
                    $pdo->prepare("INSERT INTO hero_slides (image_url, title, button_text, button_url, orden, activo) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute([$image_url, $title, $button_text, $button_url, $orden, $activo]);
                    $message = 'Slide added.';
                }
                $messageType = 'success';
            }
        }
    }
}

$slides = [];
try {
    $slides = $pdo->query("SELECT id, image_url, title, button_text, button_url, orden, activo FROM hero_slides ORDER BY orden ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Run sql/migrate_hero_slider.sql or scripts/run_migrate_hero_slider.php
}
$editing = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($slides as $s) {
        if ((int) $s['id'] === $editId) {
            $editing = $s;
            break;
        }
    }
}

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Hero Slider - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'Hero Slider']]) ?>
    <h1 class="mb-4 admin-page-title">Hero Slider</h1>
    <p class="text-muted small">Banners full-width bajo el header. Recomendado: imágenes 1920×1080px. Título y botón se superponen sobre la imagen.</p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> py-2"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white"><?= $editing ? 'Edit slide' : 'Add slide' ?></h5>
                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <?php if ($editing): ?>
                            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-2">
                            <label class="form-label text-white small">Image (1920×1080 recommended)</label>
                            <input type="file" class="form-control bg-dark text-white border-secondary" name="image" accept="image/jpeg,image/png,image/webp">
                            <?php if ($editing && !empty($editing['image_url'])): ?>
                                <p class="small text-white-50 mt-1 mb-0">Current image set. Upload to replace.</p>
                            <?php endif; ?>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Banner title</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="title" required placeholder="e.g. Next Match: Houston Derby" value="<?= htmlspecialchars($editing['title'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Button text</label>
                            <input type="text" class="form-control bg-dark text-white border-secondary" name="button_text" placeholder="Read More" value="<?= htmlspecialchars($editing['button_text'] ?? 'Read More') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Button URL</label>
                            <input type="url" class="form-control bg-dark text-white border-secondary" name="button_url" placeholder="https://..." value="<?= htmlspecialchars($editing['button_url'] ?? '') ?>">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-white small">Order</label>
                            <input type="number" class="form-control bg-dark text-white border-secondary" name="orden" min="0" value="<?= (int) ($editing['orden'] ?? 0) ?>">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" name="activo" id="activo" value="1" <?= (!$editing || (int)($editing['activo']) === 1) ? 'checked' : '' ?>>
                            <label class="form-check-label text-white small" for="activo">Active (show on slider)</label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#FF6600;border:none;"><?= $editing ? 'Update' : 'Add' ?></button>
                        <?php if ($editing): ?>
                            <a href="hero-slider.php" class="btn btn-outline-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card bg-dark border border-secondary rounded-3">
                <div class="card-body">
                    <h5 class="card-title text-white">Slides</h5>
                    <?php if (count($slides) === 0): ?>
                        <p class="text-muted mb-0">No slides yet. Add one to show the hero slider on the homepage.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-sm">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Preview</th>
                                        <th>Title</th>
                                        <th>Active</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slides as $s): ?>
                                    <tr>
                                        <td><?= (int) $s['orden'] ?></td>
                                        <td><img src="../<?= htmlspecialchars($s['image_url']) ?>" alt="" style="width:80px;height:45px;object-fit:cover;"></td>
                                        <td><?= htmlspecialchars($s['title']) ?></td>
                                        <td><?= (int) $s['activo'] === 1 ? 'Yes' : 'No' ?></td>
                                        <td>
                                            <a href="hero-slider.php?edit=<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this slide?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="delete_id" value="<?= (int) $s['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
