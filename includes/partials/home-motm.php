<?php
/** @var array|null $motmOpen */
/** @var array|null $motmWinner */
/** @var string|null $base */
if (empty($motmOpen) && empty($motmWinner)) {
    return;
}
?>
<section id="motm" class="vcf-section--dark motm-section vcf-redesign-legacy">
    <div class="container py-4">
        <?php if ($motmOpen): ?>
            <?php
            $motmEndTs = strtotime($motmOpen['ends_at']);
            $motmEndIso = date('c', $motmEndTs);
            ?>
            <h2 class="vcf-section-title vcf-section-title-line">Man of the Match</h2>
            <p class="vcf-section-desc">Vote for the player of the match. Voting closes when the timer below reaches zero. One vote per person.</p>
            <div class="vcf-countdown-wrap motm-countdown-wrap mb-3" data-countdown-iso="<?= $motmEndIso ?>" data-countdown-unix="<?= $motmEndTs ?>">
                <div class="vcf-countdown" aria-live="polite">
                    <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-days>0</span> <span class="vcf-countdown-unit">Days</span></span>
                    <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-hours>0</span> <span class="vcf-countdown-unit">Hours</span></span>
                    <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-minutes>0</span> <span class="vcf-countdown-unit">Min</span></span>
                    <span class="vcf-countdown-item"><span class="vcf-countdown-num" data-seconds>0</span> <span class="vcf-countdown-unit">Sec</span></span>
                </div>
            </div>
            <div class="row g-4 motm-nominees" data-votacion-id="<?= (int) $motmOpen['id'] ?>" data-vote-url="<?= htmlspecialchars($base ?? '') ?>/api/motm-vote.php">
                <?php foreach ($motmOpen['nominees'] as $nom): ?>
                <div class="col-md-4">
                    <div class="motm-card">
                        <?php if (!empty($nom['foto_url'])): ?>
                            <img src="<?= htmlspecialchars($base ?? '') ?>/<?= htmlspecialchars($nom['foto_url']) ?>" alt="" class="motm-card-photo" loading="lazy">
                        <?php else: ?>
                            <div class="motm-card-photo motm-card-photo-placeholder"><i class="fas fa-user" aria-hidden="true"></i></div>
                        <?php endif; ?>
                        <p class="motm-card-name"><?= htmlspecialchars($nom['nombre']) ?></p>
                        <button type="button" class="btn vcf-btn-cta motm-vote-btn" data-nominee-id="<?= (int) $nom['id'] ?>">Vote</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="text-muted small mt-2 mb-0">One vote per device. Results will be shown when voting ends.</p>
        <?php elseif ($motmWinner): ?>
            <h2 class="vcf-section-title vcf-section-title-line">Man of the Match</h2>
            <div class="motm-winner-card">
                <?php if (!empty($motmWinner['winner_foto'])): ?>
                    <img src="<?= htmlspecialchars($base ?? '') ?>/<?= htmlspecialchars($motmWinner['winner_foto']) ?>" alt="" class="motm-winner-photo" loading="lazy">
                <?php else: ?>
                    <div class="motm-winner-photo motm-winner-photo-placeholder"><i class="fas fa-trophy" aria-hidden="true"></i></div>
                <?php endif; ?>
                <div class="motm-winner-body">
                    <p class="motm-winner-label">MAN OF THE MATCH</p>
                    <h3 class="motm-winner-name"><?= htmlspecialchars($motmWinner['winner_nombre']) ?></h3>
                    <p class="motm-winner-pct"><?= (int) $motmWinner['winner_votes'] ?> votes (<?= (float) $motmWinner['winner_pct'] ?>%)</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
