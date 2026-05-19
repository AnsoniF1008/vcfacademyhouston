<?php
/**
 * includes/_news_card.php
 *
 * Partial reutilizable para una card de noticia.
 * Se usa en: home, listado de news.php, relacionadas.
 *
 * Espera la variable $noticia (o $rel, $rel se renombra a $noticia)
 */

// Soportar tanto $noticia como $rel (renombrar para reutilizar)
if (isset($rel) && !isset($noticia)) {
    $noticia = $rel;
}
if (!isset($noticia) || !is_array($noticia)) {
    return;
}
$_nc_base = $base ?? '';
?>
<article class="news-card">
    <a href="<?= htmlspecialchars(vcf_noticia_url($noticia['slug'], $_nc_base)) ?>" class="news-card-link">
        <div class="news-card-image">
            <img src="<?= htmlspecialchars(vcf_noticia_imagen_url($noticia['imagen_destacada'] ?? null, $_nc_base)) ?>"
                 alt="<?= htmlspecialchars($noticia['imagen_alt'] ?? $noticia['titulo']) ?>"
                 loading="lazy">

            <?php if (!empty($noticia['categoria_nombre'])): ?>
                <span class="news-category-badge"
                      style="background-color: <?= htmlspecialchars($noticia['categoria_color'] ?? '#FF6600') ?>">
                    <?= htmlspecialchars($noticia['categoria_nombre']) ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="news-card-body">
            <div class="news-card-meta">
                <span class="news-date">
                    <i class="far fa-calendar-alt"></i>
                    <?= htmlspecialchars(vcf_fecha_humana($noticia['fecha_publicacion'] ?? null)) ?>
                </span>
            </div>

            <h3 class="news-card-title">
                <?= htmlspecialchars($noticia['titulo']) ?>
            </h3>

            <?php if (!empty($noticia['resumen'])): ?>
                <p class="news-card-excerpt">
                    <?= htmlspecialchars(mb_strimwidth($noticia['resumen'], 0, 140, '...')) ?>
                </p>
            <?php endif; ?>

            <span class="news-card-readmore">
                Read more <i class="fas fa-arrow-right"></i>
            </span>
        </div>
    </a>
</article>
