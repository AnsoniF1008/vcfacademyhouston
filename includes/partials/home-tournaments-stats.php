<section id="tournaments" class="vcf-section--dark vcf-redesign-legacy">
    <div class="vcf-section__inner">
    <div class="container">
        <div class="tournaments-section tournaments-section--embedded">
        <div class="section-header">
            <span class="section-bar" aria-hidden="true"></span>
            <h2 class="section-title vcf-section-title vcf-section-title-line">Upcoming Tournaments &amp; Matchday</h2>
        </div>
        <p class="section-subtitle vcf-section-desc">Track our teams' progress as they compete in Houston's premier youth leagues. Check schedules, field locations, and results here.</p>

        <?php if (empty($torneosActivos) && empty($torneosPasados)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                <p>No tournaments scheduled yet. Check back soon!</p>
            </div>
        <?php endif; ?>

        <?php foreach ($torneosActivos as $tid => $torneo): ?>
            <article class="tournament-block tournament-active">
                <header class="tournament-header">
                    <div class="tournament-title-row">
                        <span class="status-dot active" aria-hidden="true"></span>
                        <h3 class="tournament-name">
                            <?= htmlspecialchars($torneo['nombre_torneo']) ?>
                            <?php if (!empty($torneo['temporada'])): ?>
                                <span class="tournament-season">— <?= htmlspecialchars($torneo['temporada']) ?></span>
                            <?php endif; ?>
                        </h3>
                        <span class="badge badge-active">Active</span>
                    </div>
                </header>
                <?php include __DIR__ . '/../_tournament_table.php'; ?>
            </article>
        <?php endforeach; ?>

        <?php if (!empty($torneosPasados)): ?>
            <details class="past-tournaments-wrapper">
                <summary class="past-tournaments-toggle">
                    <span class="toggle-icon" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                    <span class="toggle-text">View past tournaments</span>
                    <span class="toggle-count">(<?= count($torneosPasados) ?>)</span>
                </summary>
                <div class="past-tournaments-list">
                    <?php foreach ($torneosPasados as $tid => $torneo): ?>
                        <article class="tournament-block tournament-past">
                            <header class="tournament-header">
                                <div class="tournament-title-row">
                                    <span class="status-dot finished" aria-hidden="true"></span>
                                    <h3 class="tournament-name">
                                        <?= htmlspecialchars($torneo['nombre_torneo']) ?>
                                        <?php if (!empty($torneo['temporada'])): ?>
                                            <span class="tournament-season">— <?= htmlspecialchars($torneo['temporada']) ?></span>
                                        <?php endif; ?>
                                    </h3>
                                    <span class="badge badge-finished">Finished</span>
                                    <?php if (!empty($torneo['ultimo_juego'])): ?>
                                        <span class="tournament-date"><?= date('M Y', strtotime($torneo['ultimo_juego'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </header>
                            <?php
                            $resultsRequireScore = true;
                            include __DIR__ . '/../_tournament_results_table.php';
                            unset($resultsRequireScore);
                            ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </details>
        <?php endif; ?>
        </div>

        <?php if (count($topScorers) > 0): ?>
        <div class="mt-4 mb-4">
            <h3 class="stats-title">Top Scorers</h3>
            <div class="vcf-table-wrap">
                <table class="vcf-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Player</th>
                            <th>Category</th>
                            <th class="text-center">Goals</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topScorers as $i => $ts): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($ts['nombre'] . ' ' . $ts['apellido']) ?><?= $ts['dorsal'] !== null ? ' #' . (int) $ts['dorsal'] : '' ?></td>
                            <td><?= htmlspecialchars($ts['categoria_nombre']) ?></td>
                            <td class="text-center"><strong><?= (int) $ts['goles'] ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($ultimosResultados) > 0): ?>
            <h3 class="vcf-torneo-title mt-5 mb-2">Latest Results</h3>
            <?php
            $matchResultsRows = $ultimosResultados;
            include __DIR__ . '/../_tournament_results_table.php';
            unset($matchResultsRows);
            ?>
        <?php endif; ?>

    </div>
    </div>
</section>
