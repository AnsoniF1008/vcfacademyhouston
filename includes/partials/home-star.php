<?php
if (empty($star_section_visible) || empty($jugadorMes)) {
    return;
}
?>
<section id="star" class="vcf-section--dark vcf-redesign-legacy">
    <div class="vcf-section__inner">
        <div class="vcf-section__header">
            <h2 class="vcf-section__title">VCF Star of <em>the Month</em></h2>
        </div>
        <p class="vcf-section-desc" style="max-width:560px;margin-bottom:24px;">Recognizing hard work, discipline, and the VCF spirit.</p>
        <div class="vcf-star-card">
            <div class="photo-wrap">
                <?php if (!empty($jugadorMes['foto_url'])): ?>
                    <img src="<?= htmlspecialchars(($base ?? '') ? rtrim($base, '/') . '/' . $jugadorMes['foto_url'] : $jugadorMes['foto_url']) ?>" alt="<?= htmlspecialchars($jugadorMes['nombre']) ?>" loading="lazy">
                <?php else: ?>
                    <img src="<?= $base ?? '' ?>/assets/img/star-default.svg" alt="VCF Star" class="star-default-img" loading="lazy">
                <?php endif; ?>
                <?php if (!empty($jugadorMes['dorsal'])): ?>
                    <span class="star-dorsal" aria-hidden="true"><?= (int) $jugadorMes['dorsal'] ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <h3><?= htmlspecialchars($jugadorMes['nombre']) ?></h3>
                <p class="categoria"><?= htmlspecialchars($jugadorMes['categoria']) ?> · <?= htmlspecialchars($jugadorMes['mes']) ?></p>
                <p class="descripcion"><?= nl2br(htmlspecialchars($jugadorMes['descripcion_logro'])) ?></p>
            </div>
        </div>
    </div>
</section>
