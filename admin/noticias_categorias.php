<?php
/**
 * admin/noticias_categorias.php — Gestión de categorías
 */

require __DIR__ . '/includes/auth.php';
require_permission('noticias');
require __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/../includes/page_cache.php';
require_once __DIR__ . '/../includes/noticias_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        header('Location: noticias_categorias.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nombre = trim($_POST['nombre'] ?? '');
        $color  = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color'] ?? '')
                ? $_POST['color'] : '#FF6600';
        $desc   = trim($_POST['descripcion'] ?? '');

        if ($nombre !== '') {
            $slug = vcf_slug($nombre);
            // Slug único
            $stmt = $pdo->prepare("SELECT id FROM noticias_categorias WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug .= '-' . substr(uniqid(), -4);
            }
            $orden = (int) $pdo->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM noticias_categorias')->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO noticias_categorias (nombre, slug, color, descripcion, orden) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$nombre, $slug, $color, $desc, $orden]);
            admin_log('noticias.category_create', 'Created category "' . $nombre . '"');
            vcf_page_cache_clear();
        }
        header('Location: noticias_categorias.php');
        exit;
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $color  = preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color'] ?? '')
                ? $_POST['color'] : '#FF6600';
        $desc   = trim($_POST['descripcion'] ?? '');
        $activa = isset($_POST['activa']) ? 1 : 0;

        if ($id > 0 && $nombre !== '') {
            $stmt = $pdo->prepare("
                UPDATE noticias_categorias
                SET nombre = ?, color = ?, descripcion = ?, activa = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $color, $desc, $activa, $id]);
            admin_log('noticias.category_update', 'Updated category id ' . $id);
            vcf_page_cache_clear();
        }
        header('Location: noticias_categorias.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            // Las noticias quedan con categoria_id = NULL (FK ON DELETE SET NULL)
            $pdo->prepare("DELETE FROM noticias_categorias WHERE id = ?")->execute([$id]);
            admin_log('noticias.category_delete', 'Deleted category id ' . $id);
            vcf_page_cache_clear();
        }
        header('Location: noticias_categorias.php');
        exit;
    }
}

$cats = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM noticias WHERE categoria_id = c.id) AS total_noticias
    FROM noticias_categorias c
    ORDER BY c.orden ASC, c.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'News Categories - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([
        ['label' => 'News', 'url' => 'noticias.php'],
        ['label' => 'Categories'],
    ]) ?>
    <h1 class="mb-4 admin-page-title">
        <a href="noticias.php" class="text-muted text-decoration-none me-2"><i class="fas fa-arrow-left"></i></a>
        News Categories
    </h1>

    <!-- Form crear -->
    <div class="form-card" style="margin-bottom:2rem;background:#fff;padding:1.25rem;border:1px solid #e5e5e5;border-radius:8px;">
        <h3 style="margin-top:0;">New Category</h3>
        <form method="post" style="display:grid;grid-template-columns:2fr 80px 2fr auto;gap:0.5rem;align-items:end;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div>
                <label style="font-size:0.8rem;font-weight:600;">Name</label>
                <input type="text" name="nombre" required maxlength="60"
                       class="form-input" placeholder="e.g. Match Recaps">
            </div>
            <div>
                <label style="font-size:0.8rem;font-weight:600;">Color</label>
                <input type="color" name="color" value="#FF6600"
                       style="width:100%;height:38px;border:1px solid #ddd;border-radius:4px;">
            </div>
            <div>
                <label style="font-size:0.8rem;font-weight:600;">Description (optional)</label>
                <input type="text" name="descripcion" maxlength="200"
                       class="form-input" placeholder="Short description">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add
            </button>
        </form>
    </div>

    <!-- Tabla -->
    <table class="admin-table">
        <thead>
            <tr>
                <th>Color</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Articles</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cats as $c): ?>
                <tr>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">

                        <td>
                            <input type="color" name="color" value="<?= htmlspecialchars($c['color']) ?>"
                                   style="width:50px;height:30px;border:1px solid #ddd;border-radius:4px;">
                        </td>
                        <td>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($c['nombre']) ?>"
                                   required maxlength="60" class="form-input"
                                   style="min-width:160px;">
                        </td>
                        <td>
                            <code style="background:#f5f5f5;padding:2px 6px;border-radius:3px;font-size:0.8rem;">
                                <?= htmlspecialchars($c['slug']) ?>
                            </code>
                        </td>
                        <td><?= (int) $c['total_noticias'] ?></td>
                        <td>
                            <label style="display:inline-flex;align-items:center;gap:0.4rem;">
                                <input type="checkbox" name="activa" value="1"
                                    <?= $c['activa'] ? 'checked' : '' ?>>
                                <span>Active</span>
                            </label>
                        </td>
                        <td style="display:flex;gap:0.3rem;">
                            <button type="submit" class="btn-icon" title="Save changes">
                                <i class="fas fa-save"></i>
                            </button>
                    </form>

                    <form method="post" style="display:inline;"
                          onsubmit="return confirm('Delete this category? Articles will become uncategorized.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                        <button type="submit" class="btn-icon btn-icon-danger" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                        </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
