<?php
/**
 * Latest News — grid de 3 en el home.
 * Requiere: $ultimasNoticias (index.php).
 */
$bNews = $b ?? $base ?? '';
?>
<?php if (!empty($ultimasNoticias)): ?>
<section class="news-section" id="news">
    <div class="container">
        <div class="section-header">
            <span class="section-bar" aria-hidden="true"></span>
            <h2 class="section-title vcf-section-title vcf-section-title-line">Latest News</h2>
            <a href="<?= htmlspecialchars($bNews) ?>/news.php" class="section-link-all">
                View all news <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
        <p class="section-subtitle vcf-section-desc">
            Stay up to date with match recaps, tournament coverage and academy announcements.
        </p>

        <div class="news-grid">
            <?php foreach ($ultimasNoticias as $noticia): ?>
                <?php include __DIR__ . '/../_news_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
