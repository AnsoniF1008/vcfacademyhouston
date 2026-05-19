<?php
require __DIR__ . '/includes/auth.php';
require_permission('noticias');
require __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../includes/page_cache.php';
require_once __DIR__ . '/../includes/noticias_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        http_response_code(403);
        exit('Invalid request');
    }

    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle_publicado' && $id > 0) {
        header('Content-Type: application/json');
        try {
            $pdo->prepare('UPDATE noticias SET publicado = 1 - publicado WHERE id = ?')->execute([$id]);
            admin_log('noticias.toggle_publish', 'Toggled publish status for article id ' . $id);
            vcf_page_cache_clear();
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            error_log('Toggle publicado: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'DB error']);
        }
        exit;
    }

    if ($action === 'delete' && $id > 0) {
        try {
            $stmt = $pdo->prepare('SELECT titulo, imagen_destacada FROM noticias WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['imagen_destacada'])) {
                $path = __DIR__ . '/../' . ltrim($row['imagen_destacada'], '/');
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            $pdo->prepare('DELETE FROM noticias WHERE id = ?')->execute([$id]);
            admin_log('noticias.delete', 'Deleted article "' . ($row['titulo'] ?? $id) . '" (id ' . $id . ')');
            vcf_page_cache_clear();
            header('Location: noticias.php?msg=deleted');
        } catch (PDOException $e) {
            error_log('Delete noticia: ' . $e->getMessage());
            header('Location: noticias.php?msg=error');
        }
        exit;
    }
}

$filtroCategoria = $_GET['cat'] ?? '';
$busqueda = trim($_GET['q'] ?? '');

$sql = "
    SELECT n.id, n.titulo, n.slug, n.publicado, n.destacada, n.fecha_publicacion,
           n.views, n.imagen_destacada,
           c.nombre AS categoria_nombre, c.color AS categoria_color
    FROM noticias n
    LEFT JOIN noticias_categorias c ON c.id = n.categoria_id
    WHERE 1=1
";
$params = [];
if ($filtroCategoria !== '') {
    $sql .= ' AND c.slug = :cat ';
    $params[':cat'] = $filtroCategoria;
}
if ($busqueda !== '') {
    $sql .= ' AND n.titulo LIKE :q ';
    $params[':q'] = '%' . $busqueda . '%';
}
$sql .= ' ORDER BY n.created_at DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categorias = vcf_noticias_categorias_activas($pdo);

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Manage News - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([['label' => 'News']]) ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <h1 class="mb-0 admin-page-title"><i class="far fa-newspaper me-2"></i>News</h1>
        <a href="noticia_edit.php" class="btn btn-primary btn-admin-primary">
            <i class="fas fa-plus"></i> New Article
        </a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-<?= $_GET['msg'] === 'error' ? 'danger' : 'success' ?> py-2">
            <?php
            echo match ($_GET['msg']) {
                'created' => 'Article created successfully.',
                'updated' => 'Article updated.',
                'deleted' => 'Article deleted.',
                'error'   => 'An error occurred.',
                default   => '',
            };
            ?>
        </div>
    <?php endif; ?>

    <form method="get" class="row g-2 mb-4 align-items-end">
        <div class="col-md-5">
            <label class="form-label text-white small mb-1">Search</label>
            <input type="text" name="q" class="form-control bg-dark text-white border-secondary"
                   placeholder="Search by title..." value="<?= htmlspecialchars($busqueda) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label text-white small mb-1">Category</label>
            <select name="cat" class="form-select bg-dark text-white border-secondary">
                <option value="">All categories</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= htmlspecialchars($c['slug']) ?>" <?= $filtroCategoria === $c['slug'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="noticias.php" class="btn btn-outline-secondary">Reset</a>
            <a href="noticias_categorias.php" class="btn btn-outline-secondary ms-auto" title="Manage categories">
                <i class="fas fa-tags"></i>
            </a>
        </div>
    </form>

    <div class="card bg-dark border border-secondary rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive admin-table-wrap">
                <table class="table table-dark table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th>Views</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($noticias)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">
                            No articles yet. <a href="noticia_edit.php">Create the first one</a>.
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($noticias as $n): ?>
                        <tr data-id="<?= (int) $n['id'] ?>">
                            <td>
                                <?php if (!empty($n['imagen_destacada'])): ?>
                                    <img src="../<?= htmlspecialchars($n['imagen_destacada']) ?>" alt="" class="admin-news-thumb">
                                <?php else: ?>
                                    <span class="admin-news-thumb-placeholder"><i class="far fa-image"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong class="text-white"><?= htmlspecialchars($n['titulo']) ?></strong>
                                <div class="small text-muted">/news.php?slug=<?= htmlspecialchars($n['slug']) ?></div>
                            </td>
                            <td>
                                <?php if ($n['categoria_nombre']): ?>
                                    <span class="badge" style="background:<?= htmlspecialchars($n['categoria_color']) ?>;color:#000;">
                                        <?= htmlspecialchars($n['categoria_nombre']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm js-toggle-publish <?= $n['publicado'] ? 'btn-success' : 'btn-outline-secondary' ?>"
                                        data-id="<?= (int) $n['id'] ?>">
                                    <?= $n['publicado'] ? 'Published' : 'Draft' ?>
                                </button>
                            </td>
                            <td class="text-nowrap">
                                <?= $n['fecha_publicacion'] ? date('M j, Y', strtotime($n['fecha_publicacion'])) : '—' ?>
                            </td>
                            <td><?= number_format((int) $n['views']) ?></td>
                            <td class="text-nowrap">
                                <a href="../news.php?slug=<?= urlencode($n['slug']) ?>" target="_blank" rel="noopener"
                                   class="btn btn-sm btn-outline-secondary" title="View"><i class="fas fa-external-link-alt"></i></a>
                                <a href="noticia_edit.php?id=<?= (int) $n['id'] ?>" class="btn btn-sm btn-admin-primary" title="Edit"><i class="fas fa-pen"></i></a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this article?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const csrf = <?= json_encode(csrf_token()) ?>;
    document.querySelectorAll('.js-toggle-publish').forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.dataset.id;
            this.disabled = true;
            try {
                const fd = new FormData();
                fd.append('action', 'toggle_publicado');
                fd.append('id', id);
                fd.append('csrf_token', csrf);
                const res = await fetch(window.location.pathname, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    const pub = this.classList.contains('btn-success');
                    this.classList.toggle('btn-success', !pub);
                    this.classList.toggle('btn-outline-secondary', pub);
                    this.textContent = pub ? 'Draft' : 'Published';
                } else {
                    alert('Error saving status');
                }
            } catch (e) {
                alert('Network error');
            } finally {
                this.disabled = false;
            }
        });
    });
})();
</script>
<style>
.admin-news-thumb { width: 60px; height: 40px; object-fit: cover; border-radius: 4px; }
.admin-news-thumb-placeholder {
    display: inline-flex; width: 60px; height: 40px; align-items: center; justify-content: center;
    background: #222; border-radius: 4px; color: #666;
}
</style>
<?php require __DIR__ . '/../includes/footer.php'; ?>
