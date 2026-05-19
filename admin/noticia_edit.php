<?php
/**
 * admin/noticia_edit.php — Crear/editar noticia
 *
 * URL:
 *   noticia_edit.php       → crear nueva
 *   noticia_edit.php?id=N  → editar existente
 */

require __DIR__ . '/includes/auth.php';
require_permission('noticias');
require __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../includes/page_cache.php';
require_once __DIR__ . '/../includes/noticias_helper.php';

$id = (int) ($_GET['id'] ?? 0);
$noticia = [
    'id' => 0, 'titulo' => '', 'slug' => '', 'categoria_id' => null,
    'resumen' => '', 'contenido' => '', 'imagen_destacada' => '',
    'imagen_alt' => '', 'autor' => 'VCF Academy Houston',
    'publicado' => 0, 'destacada' => 0, 'fecha_publicacion' => '',
    'meta_description' => '',
];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM noticias WHERE id = ?");
    $stmt->execute([$id]);
    $fetched = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$fetched) {
        header('Location: noticias.php?msg=error');
        exit;
    }
    $noticia = $fetched;
}

$categorias = vcf_noticias_categorias_activas($pdo);

// === Procesar formulario ===
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = 'Invalid request. Please try again.';
    } else {

    $titulo            = trim($_POST['titulo'] ?? '');
    $slug              = trim($_POST['slug'] ?? '');
    $categoria_id      = $_POST['categoria_id'] ? (int) $_POST['categoria_id'] : null;
    $resumen           = trim($_POST['resumen'] ?? '');
    $contenido         = $_POST['contenido'] ?? '';
    $imagen_alt        = trim($_POST['imagen_alt'] ?? '');
    $autor             = trim($_POST['autor'] ?? 'VCF Academy Houston');
    $publicado         = isset($_POST['publicado']) ? 1 : 0;
    $destacada         = isset($_POST['destacada']) ? 1 : 0;
    $fecha_publicacion = $_POST['fecha_publicacion'] ?? '';
    $meta_description  = trim($_POST['meta_description'] ?? '');

    // Validación
    if ($titulo === '') $errors[] = 'Title is required';
    if (mb_strlen($titulo) > 200) $errors[] = 'Title too long (max 200 chars)';
    if ($contenido === '') $errors[] = 'Content is required';

    // Auto-generar slug si está vacío
    if ($slug === '' && $titulo !== '') {
        $slug = vcf_slug($titulo);
    } else {
        $slug = vcf_slug($slug);
    }

    // Asegurar slug único
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM noticias WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            $slug .= '-' . substr(uniqid(), -5);
        }
    }

    // Si publicado=1 y sin fecha, ponerla a ahora
    if ($publicado && empty($fecha_publicacion)) {
        $fecha_publicacion = date('Y-m-d H:i:s');
    }
    if ($fecha_publicacion === '') $fecha_publicacion = null;

    // Subida de imagen
    $imagen_destacada = $noticia['imagen_destacada'];
    if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['imagen']['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed, true)) {
            $errors[] = 'Image must be JPG, PNG or WEBP';
        } elseif ($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
            $errors[] = 'Image too large (max 5 MB)';
        } else {
            $uploadDir = __DIR__ . '/../assets/uploads/noticias/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

            $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
            $ext = strtolower(preg_replace('/[^a-z0-9]/', '', $ext));
            $filename = 'noticia-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destPath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $destPath)) {
                // Borrar imagen anterior si existía
                if ($imagen_destacada && file_exists(__DIR__ . '/../' . $imagen_destacada)) {
                    @unlink(__DIR__ . '/../' . $imagen_destacada);
                }
                $imagen_destacada = 'assets/uploads/noticias/' . $filename;
            } else {
                $errors[] = 'Failed to save uploaded image';
            }
        }
    }

    if (empty($errors)) {
        try {
            if ($id > 0) {
                // UPDATE
                $stmt = $pdo->prepare("
                    UPDATE noticias SET
                        titulo = ?, slug = ?, categoria_id = ?, resumen = ?,
                        contenido = ?, imagen_destacada = ?, imagen_alt = ?,
                        autor = ?, publicado = ?, destacada = ?,
                        fecha_publicacion = ?, meta_description = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $titulo, $slug, $categoria_id, $resumen,
                    $contenido, $imagen_destacada, $imagen_alt,
                    $autor, $publicado, $destacada,
                    $fecha_publicacion, $meta_description,
                    $id,
                ]);
                $msg = 'updated';
            } else {
                // INSERT
                $stmt = $pdo->prepare("
                    INSERT INTO noticias
                        (titulo, slug, categoria_id, resumen, contenido,
                         imagen_destacada, imagen_alt, autor, publicado,
                         destacada, fecha_publicacion, meta_description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $titulo, $slug, $categoria_id, $resumen, $contenido,
                    $imagen_destacada, $imagen_alt, $autor, $publicado,
                    $destacada, $fecha_publicacion, $meta_description,
                ]);
                $msg = 'created';
            }

            admin_log('noticias.' . ($id > 0 ? 'update' : 'create'), ($id > 0 ? 'Updated' : 'Created') . ' article "' . $titulo . '"');
            vcf_page_cache_clear();
            header('Location: noticias.php?msg=' . $msg);
            exit;

        } catch (PDOException $e) {
            error_log('Save noticia: ' . $e->getMessage());
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

        // Repoblar form con datos enviados si hubo error
        $noticia = array_merge($noticia, [
            'titulo' => $titulo, 'slug' => $slug, 'categoria_id' => $categoria_id,
            'resumen' => $resumen, 'contenido' => $contenido, 'imagen_alt' => $imagen_alt,
            'autor' => $autor, 'publicado' => $publicado, 'destacada' => $destacada,
            'fecha_publicacion' => $fecha_publicacion, 'meta_description' => $meta_description,
            'imagen_destacada' => $imagen_destacada,
        ]);
    }
}

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = ($id > 0 ? 'Edit Article' : 'New Article') . ' - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([
        ['label' => 'News', 'url' => 'noticias.php'],
        ['label' => $id > 0 ? 'Edit' : 'New'],
    ]) ?>
    <h1 class="mb-4 admin-page-title">
        <a href="noticias.php" class="text-muted text-decoration-none me-2"><i class="fas fa-arrow-left"></i></a>
        <?= $id > 0 ? 'Edit Article' : 'New Article' ?>
    </h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Please fix:</strong>
            <ul style="margin:0.5rem 0 0 1.25rem;">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="news-form">
        <?= csrf_field() ?>

        <div class="form-grid">
            <!-- Columna principal -->
            <div class="form-main">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="titulo" required maxlength="200"
                           value="<?= htmlspecialchars($noticia['titulo']) ?>"
                           class="form-input" id="titulo">
                </div>

                <div class="form-group">
                    <label>Slug (URL)</label>
                    <input type="text" name="slug" maxlength="220"
                           value="<?= htmlspecialchars($noticia['slug']) ?>"
                           class="form-input" id="slug"
                           placeholder="auto-generated from title">
                    <small>Leave empty to auto-generate. Used in URL: /news.php?slug=...</small>
                </div>

                <div class="form-group">
                    <label>Summary / Excerpt</label>
                    <textarea name="resumen" rows="3" maxlength="500"
                              class="form-input"><?= htmlspecialchars($noticia['resumen']) ?></textarea>
                    <small>Short preview shown in cards (max ~200 chars recommended).</small>
                </div>

                <div class="form-group">
                    <label>Content *</label>
                    <textarea name="contenido" rows="18" required
                              class="form-input news-content-editor"
                              id="contenido"><?= htmlspecialchars($noticia['contenido']) ?></textarea>
                    <small>You can paste HTML. Allowed: paragraphs, headings, lists, images, links, blockquotes, YouTube/Vimeo iframes.</small>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="form-sidebar">
                <div class="form-card">
                    <h3>Publish</h3>

                    <label class="form-check">
                        <input type="checkbox" name="publicado" value="1"
                            <?= $noticia['publicado'] ? 'checked' : '' ?>>
                        Published
                    </label>
                    <small style="display:block;margin-bottom:1rem;color:#888;">
                        Uncheck to save as draft.
                    </small>

                    <label class="form-check">
                        <input type="checkbox" name="destacada" value="1"
                            <?= $noticia['destacada'] ? 'checked' : '' ?>>
                        Featured
                    </label>
                    <small style="display:block;margin-bottom:1rem;color:#888;">
                        Featured articles can be highlighted in hero/home.
                    </small>

                    <div class="form-group">
                        <label>Publish date</label>
                        <input type="datetime-local" name="fecha_publicacion"
                               value="<?= $noticia['fecha_publicacion']
                                    ? date('Y-m-d\TH:i', strtotime($noticia['fecha_publicacion']))
                                    : '' ?>"
                               class="form-input">
                        <small>Leave empty for "now" when publishing.</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i>
                        <?= $id > 0 ? 'Update' : 'Create' ?> Article
                    </button>
                </div>

                <div class="form-card">
                    <h3>Category</h3>
                    <select name="categoria_id" class="form-input">
                        <option value="">— Uncategorized —</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"
                                <?= ((int) $noticia['categoria_id']) === ((int) $c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-card">
                    <h3>Featured Image</h3>
                    <?php if (!empty($noticia['imagen_destacada'])): ?>
                        <img src="../<?= htmlspecialchars($noticia['imagen_destacada']) ?>"
                             alt="" style="width:100%;border-radius:6px;margin-bottom:0.5rem;">
                    <?php endif; ?>
                    <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
                           class="form-input">
                    <small>JPG, PNG, WEBP. Max 5MB. Recommended 1200x675 (16:9).</small>

                    <div class="form-group" style="margin-top:0.75rem;">
                        <label>Image alt text</label>
                        <input type="text" name="imagen_alt" maxlength="200"
                               value="<?= htmlspecialchars($noticia['imagen_alt']) ?>"
                               class="form-input"
                               placeholder="Describe the image for accessibility">
                    </div>
                </div>

                <div class="form-card">
                    <h3>Meta</h3>
                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="autor" maxlength="100"
                               value="<?= htmlspecialchars($noticia['autor']) ?>"
                               class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Meta description (SEO)</label>
                        <textarea name="meta_description" rows="3" maxlength="300"
                                  class="form-input"
                                  placeholder="Short description for search engines"><?= htmlspecialchars($noticia['meta_description']) ?></textarea>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
// Auto-slug en el campo
(function () {
    const titulo = document.getElementById('titulo');
    const slug = document.getElementById('slug');
    if (!titulo || !slug) return;

    function slugify(t) {
        return t.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    titulo.addEventListener('input', function () {
        if (!slug.dataset.touched) {
            slug.value = slugify(this.value);
        }
    });
    slug.addEventListener('input', function () {
        slug.dataset.touched = '1';
    });
})();
</script>

<style>
.news-form .form-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1.5rem;
}
.form-main, .form-sidebar { display: flex; flex-direction: column; gap: 1rem; }
.form-card {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 1.25rem;
}
.form-card h3 {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #555;
    margin: 0 0 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
}
.form-group { margin-bottom: 1rem; }
.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.4rem;
    font-size: 0.875rem;
    color: #333;
}
.form-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 0.9rem;
    font-family: inherit;
}
.form-input:focus {
    outline: none;
    border-color: #FF6600;
    box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.1);
}
.news-content-editor {
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    line-height: 1.5;
}
.form-group small {
    display: block;
    margin-top: 0.3rem;
    color: #888;
    font-size: 0.75rem;
}
.form-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    cursor: pointer;
}
.btn-block { width: 100%; }
.alert {
    padding: 0.75rem 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}
.alert-danger { background: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
.alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }

@media (max-width: 900px) {
    .news-form .form-grid { grid-template-columns: 1fr; }
}
</style>

</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
