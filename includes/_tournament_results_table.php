<?php
/**
 * Tabla de resultados (Date | Home | Score | Away | Info).
 * Scope: $torneo['juegos'], o $matchResultsRows si se define antes del include.
 */

$_tr_rows = $matchResultsRows ?? ($torneo['juegos'] ?? []);
$_tr_base = $base ?? '';
$_tr_now = new DateTime('now', new DateTimeZone('America/Chicago'));
$_tr_today = $_tr_now->format('Y-m-d');
$_tr_time  = $_tr_now->format('H:i:s');

// Solo partidos ya jugados (o con marcador registrado)
$_tr_played = [];
foreach ($_tr_rows as $j) {
    $fecha = $j['fecha'] ?? '';
    $hora  = $j['hora'] ?? null;
    $hasScore = (isset($j['goles_vcf']) && $j['goles_vcf'] !== null)
        || (isset($j['goles_rival']) && $j['goles_rival'] !== null);

    $isPast = $fecha < $_tr_today
        || ($fecha === $_tr_today && $hora && $hora < $_tr_time);

    if (!empty($resultsRequireScore) && !$hasScore) {
        continue;
    }
    if ($hasScore || $isPast) {
        $_tr_played[] = $j;
    }
}

if (empty($_tr_played)) {
    echo '<div class="empty-games">No results recorded for this tournament yet.</div>';
    return;
}

usort($_tr_played, function ($a, $b) {
    $cmp = strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
    if ($cmp !== 0) {
        return $cmp;
    }
    return strcmp($b['hora'] ?? '', $a['hora'] ?? '');
});
?>
<div class="vcf-table-wrap tournament-results-wrap">
    <table class="vcf-table vcf-table-results tournament-results-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Home</th>
                <th>Score</th>
                <th>Away</th>
                <th>Info</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($_tr_played as $r): ?>
            <?php
                $gv = (int) ($r['goles_vcf'] ?? 0);
                $gr = (int) ($r['goles_rival'] ?? 0);
                $hasScore = (isset($r['goles_vcf']) && $r['goles_vcf'] !== null)
                    || (isset($r['goles_rival']) && $r['goles_rival'] !== null);

                if ($hasScore) {
                    if ($gv > $gr) {
                        $resultOutcome = 'W';
                        $vcfClass = 'vcf-result-winner';
                        $rivalClass = 'vcf-result-loser';
                        $scoreVcfClass = 'vcf-result-winner';
                        $scoreRivalClass = 'vcf-result-loser';
                    } elseif ($gv < $gr) {
                        $resultOutcome = 'L';
                        $vcfClass = 'vcf-result-loser';
                        $rivalClass = 'vcf-result-winner';
                        $scoreVcfClass = 'vcf-result-loser';
                        $scoreRivalClass = 'vcf-result-winner';
                    } else {
                        $resultOutcome = 'D';
                        $vcfClass = $rivalClass = 'vcf-result-draw';
                        $scoreVcfClass = $scoreRivalClass = 'vcf-result-draw';
                    }
                } else {
                    $resultOutcome = null;
                    $vcfClass = $rivalClass = $scoreVcfClass = $scoreRivalClass = 'vcf-result-draw';
                }

                $rivalName = !empty($r['rival']) ? htmlspecialchars($r['rival']) : '—';
                $rivalLogoUrl = !empty($r['rival_logo_url']) ? $r['rival_logo_url'] : null;
            ?>
            <tr>
                <td data-label="Date"><?= date('M j', strtotime($r['fecha'])) ?></td>
                <td data-label="Home">
                    <span class="vcf-result-team">
                        <?php if (!empty($vcf_crest_file)): ?>
                            <img src="<?= htmlspecialchars($_tr_base) ?>/assets/img/<?= htmlspecialchars($vcf_crest_file) ?>"
                                 alt="VCF Houston" class="vcf-team-logo" width="28" height="28" loading="lazy">
                        <?php else: ?>
                            <span class="vcf-team-logo vcf-team-logo-placeholder" aria-hidden="true"><i class="fas fa-shield"></i></span>
                        <?php endif; ?>
                        <span class="<?= $vcfClass ?>">VCF Houston</span>
                    </span>
                </td>
                <td class="vcf-score-cell" data-label="Score">
                    <?php if ($hasScore): ?>
                        <span class="<?= $scoreVcfClass ?>"><?= $gv ?></span> – <span class="<?= $scoreRivalClass ?>"><?= $gr ?></span>
                    <?php else: ?>
                        <span class="vcf-result-draw">—</span>
                    <?php endif; ?>
                </td>
                <td data-label="Away">
                    <span class="vcf-result-team">
                        <?php if ($rivalLogoUrl): ?>
                            <img src="<?= htmlspecialchars($rivalLogoUrl) ?>" alt="<?= $rivalName ?>"
                                 class="vcf-team-logo" width="28" height="28" loading="lazy"
                                 onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                            <span class="vcf-team-logo vcf-team-logo-placeholder" style="display:none;" aria-hidden="true"><i class="fas fa-shield"></i></span>
                        <?php else: ?>
                            <span class="vcf-team-logo vcf-team-logo-placeholder" aria-hidden="true"><i class="fas fa-shield"></i></span>
                        <?php endif; ?>
                        <span class="<?= $rivalClass ?>"><?= $rivalName ?></span>
                    </span>
                </td>
                <td class="vcf-table-actions" data-label="Info">
                    <?php if ($resultOutcome === 'W'): ?>
                        <span class="vcf-badge-w">W</span>
                    <?php elseif ($resultOutcome === 'L'): ?>
                        <span class="vcf-badge-l">L</span>
                    <?php elseif ($resultOutcome === 'D'): ?>
                        <span class="vcf-badge-d">D</span>
                    <?php else: ?>
                        <span class="vcf-result-draw">—</span>
                    <?php endif; ?>
                    <?php if (!empty($r['id'])): ?>
                        <a href="<?= htmlspecialchars($_tr_base) ?>/match.php?id=<?= (int) $r['id'] ?>"
                           class="vcf-btn-link ms-2" title="View match details">Details</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
