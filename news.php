<?php
/**
 * news.php — Página pública de noticias
 *
 * Modos:
 *   /news.php                 → listado paginado
 *   /news.php?slug=algo       → detalle de una noticia
 *   /news.php?cat=match-recaps → listado filtrado por categoría
 *   /news.php?pagina=2        → paginación
 *
 * Colocar este archivo en la raíz del proyecto.
 */

require __DIR__ . '/includes/page_cache.php';

// Cache de 10 minutos
if (vcf_page_cache_try_serve(600)) {
    exit;
}

require __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/noticias_helper.php';

vcf_page_cache_start(600);

$slug          = $_GET['slug']   ?? null;
$categoriaSlug = $_GET['cat']    ?? null;
$paginaActual  = max(1, (int) ($_GET['pagina'] ?? 1));

$noticiaDetalle = null;
$listado        = null;
$categorias     = vcf_noticias_categorias_activas($pdo);

if ($slug) {
    // MODO DETALLE
    $noticiaDetalle = vcf_noticia_por_slug($pdo, $slug);

    if (!$noticiaDetalle) {
        http_response_code(404);
        $page_title = 'News not found | VCF Academy Houston';
        require __DIR__ . '/includes/header.php';
        echo '<div class="container" style="padding:4rem 1rem;text-align:center;">';
        echo '<h1>404 — News article not found</h1>';
        echo '<p>The article you are looking for does not exist or has been removed.</p>';
        echo '<a href="' . htmlspecialchars(($base ?? '') . '/news.php') . '" class="btn btn-primary">Back to news</a>';
        echo '</div>';
        require __DIR__ . '/includes/footer.php';
        exit;
    }

    // Incrementar views (no rompe cache porque es UPDATE diferido)
    vcf_noticia_increment_views($pdo, (int) $noticiaDetalle['id']);

    // Noticias relacionadas (misma categoría, excluyendo la actual)
    $relacionadas = vcf_noticias_ultimas(
        $pdo,
        3,
        $noticiaDetalle['categoria_id'] ?? null,
        (int) $noticiaDetalle['id']
    );

    $page_title       = htmlspecialchars($noticiaDetalle['titulo']) . ' | VCF Academy Houston';
    $page_description = $noticiaDetalle['meta_description']
        ?? mb_strimwidth($noticiaDetalle['resumen'] ?? '', 0, 160, '...');
    $page_og_image    = vcf_noticia_imagen_url($noticiaDetalle['imagen_destacada'], $base ?? '');
} else {
    // MODO LISTADO
    $listado = vcf_noticias_listar($pdo, $paginaActual, 9, $categoriaSlug);

    $page_title = 'News | VCF Academy Houston';
    if ($categoriaSlug) {
        foreach ($categorias as $c) {
            if ($c['slug'] === $categoriaSlug) {
                $page_title = $c['nombre'] . ' | News | VCF Academy Houston';
                break;
            }
        }
    }
    $page_description = 'Latest news from VCF Academy Houston: match recaps, tournaments, academy announcements and player stories.';
}

$page_active = 'news';

require __DIR__ . '/includes/header.php';
?>

<?php if ($noticiaDetalle): ?>

    <!-- ===== DETALLE DE NOTICIA ===== -->
    <article class="news-detail">
        <!-- Hero con imagen destacada -->
        <?php if (!empty($noticiaDetalle['imagen_destacada'])): ?>
            <div class="news-detail-hero"
                 style="background-image: url('<?= htmlspecialchars(vcf_noticia_imagen_url($noticiaDetalle['imagen_destacada'], $base ?? '')) ?>');">
                <div class="news-detail-hero-overlay">
                    <div class="container">
                        <?php if (!empty($noticiaDetalle['categoria_nombre'])): ?>
                            <a href="<?= htmlspecialchars($base ?? '') ?>/news.php?cat=<?= urlencode($noticiaDetalle['categoria_slug']) ?>"
                               class="news-category-badge"
                               style="background-color: <?= htmlspecialchars($noticiaDetalle['categoria_color']) ?>">
                                <?= htmlspecialchars($noticiaDetalle['categoria_nombre']) ?>
                            </a>
                        <?php endif; ?>
                        <h1 class="news-detail-title"><?= htmlspecialchars($noticiaDetalle['titulo']) ?></h1>
                        <div class="news-detail-meta">
                            <span><i class="far fa-calendar-alt"></i> <?= htmlspecialchars(vcf_fecha_humana($noticiaDetalle['fecha_publicacion'])) ?></span>
                            <?php if (!empty($noticiaDetalle['autor'])): ?>
                                <span><i class="far fa-user"></i> <?= htmlspecialchars($noticiaDetalle['autor']) ?></span>
                            <?php endif; ?>
                            <span><i class="far fa-eye"></i> <?= number_format((int) $noticiaDetalle['views']) ?> views</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <header class="news-detail-header-noimg container">
                <?php if (!empty($noticiaDetalle['categoria_nombre'])): ?>
                    <a href="<?= htmlspecialchars($base ?? '') ?>/news.php?cat=<?= urlencode($noticiaDetalle['categoria_slug']) ?>"
                       class="news-category-badge"
                       style="background-color: <?= htmlspecialchars($noticiaDetalle['categoria_color']) ?>">
                        <?= htmlspecialchars($noticiaDetalle['categoria_nombre']) ?>
                    </a>
                <?php endif; ?>
                <h1 class="news-detail-title"><?= htmlspecialchars($noticiaDetalle['titulo']) ?></h1>
                <div class="news-detail-meta">
                    <span><i class="far fa-calendar-alt"></i> <?= htmlspecialchars(vcf_fecha_humana($noticiaDetalle['fecha_publicacion'])) ?></span>
                    <?php if (!empty($noticiaDetalle['autor'])): ?>
                        <span><i class="far fa-user"></i> <?= htmlspecialchars($noticiaDetalle['autor']) ?></span>
                    <?php endif; ?>
                </div>
            </header>
        <?php endif; ?>

        <!-- Contenido -->
        <div class="container">
            <div class="news-detail-body">
                <?php if (!empty($noticiaDetalle['resumen'])): ?>
                    <p class="news-detail-lead"><?= htmlspecialchars($noticiaDetalle['resumen']) ?></p>
                <?php endif; ?>

                <div class="news-detail-content">
                    <?php
                    // El contenido es HTML (editor WYSIWYG). Confiamos en el admin
                    // pero filtramos scripts/iframes peligrosos.
                    $html = $noticiaDetalle['contenido'];
                    $html = preg_replace('#<script[^>]*>.*?</script>#is', '', $html);
                    $html = preg_replace('#<iframe(?![^>]*(youtube|vimeo|instagram|twitter|x\.com))[^>]*>.*?</iframe>#is', '', $html);
                    echo $html;
                    ?>
                </div>

                <!-- Botón compartir simple -->
                <div class="news-detail-share">
                    <span class="share-label">Share:</span>
                    <?php
                    $url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'vcfacademyhouston.com')
                         . '/news.php?slug=' . urlencode($noticiaDetalle['slug']);
                    $encUrl   = urlencode($url);
                    $encTitle = urlencode($noticiaDetalle['titulo']);
                    ?>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $encUrl ?>" target="_blank" rel="noopener" aria-label="Share on Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/intent/tweet?url=<?= $encUrl ?>&text=<?= $encTitle ?>" target="_blank" rel="noopener" aria-label="Share on X"><i class="fab fa-x-twitter"></i></a>
                    <a href="https://wa.me/?text=<?= $encTitle ?>%20<?= $encUrl ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>

            <!-- Relacionadas -->
            <?php if (!empty($relacionadas)): ?>
                <section class="news-related">
                    <h2 class="section-title-small">More from <?= htmlspecialchars($noticiaDetalle['categoria_nombre'] ?? 'News') ?></h2>
                    <div class="news-grid">
                        <?php foreach ($relacionadas as $rel): ?>
                            <?php include __DIR__ . '/includes/_news_card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <div class="news-detail-back">
                <a href="<?= htmlspecialchars($base ?? '') ?>/news.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to all news
                </a>
            </div>
        </div>
    </article>

<?php else: ?>

    <!-- ===== LISTADO DE NOTICIAS ===== -->
    <div class="news-listing">
        <div class="container">

            <header class="news-listing-header">
                <h1 class="page-title">
                    <?php if ($categoriaSlug): ?>
                        <?php foreach ($categorias as $c): ?>
                            <?php if ($c['slug'] === $categoriaSlug): ?>
                                <?= htmlspecialchars($c['nombre']) ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        All News
                    <?php endif; ?>
                </h1>
                <p class="page-subtitle">
                    <?= (int) $listado['total'] ?> article<?= $listado['total'] === 1 ? '' : 's' ?>
                </p>
            </header>

            <!-- Filtros de categoría -->
            <nav class="news-filters">
                <a href="<?= htmlspecialchars($base ?? '') ?>/news.php" class="filter-chip <?= !$categoriaSlug ? 'active' : '' ?>">All</a>
                <?php foreach ($categorias as $c): ?>
                    <a href="<?= htmlspecialchars($base ?? '') ?>/news.php?cat=<?= urlencode($c['slug']) ?>"
                       class="filter-chip <?= $categoriaSlug === $c['slug'] ? 'active' : '' ?>"
                       style="<?= $categoriaSlug === $c['slug']
                            ? 'background-color:' . htmlspecialchars($c['color']) . ';color:#000;'
                            : '' ?>">
                        <?= htmlspecialchars($c['nombre']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if (empty($listado['items'])): ?>
                <div class="empty-state">
                    <i class="far fa-newspaper"></i>
                    <p>No news in this category yet. Check back soon!</p>
                </div>
            <?php else: ?>
                <div class="news-grid">
                    <?php foreach ($listado['items'] as $noticia): ?>
                        <?php include __DIR__ . '/includes/_news_card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($listado['paginas'] > 1): ?>
                    <nav class="news-pagination" aria-label="News pagination">
                        <?php
                        $newsPageBase = htmlspecialchars($base ?? '') . '/news.php?' . ($categoriaSlug ? 'cat=' . urlencode($categoriaSlug) . '&' : '');
                        ?>

                        <?php if ($paginaActual > 1): ?>
                            <a href="<?= $newsPageBase ?>pagina=<?= $paginaActual - 1 ?>" class="page-btn">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $listado['paginas']; $p++): ?>
                            <a href="<?= $newsPageBase ?>pagina=<?= $p ?>"
                               class="page-btn <?= $p === $paginaActual ? 'active' : '' ?>">
                                <?= $p ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($paginaActual < $listado['paginas']): ?>
                            <a href="<?= $newsPageBase ?>pagina=<?= $paginaActual + 1 ?>" class="page-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
