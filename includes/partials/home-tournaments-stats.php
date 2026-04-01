<section id="tournaments" class="vcf-section--dark vcf-redesign-legacy">
    <div class="vcf-section__inner">
    <div class="container">
        <h2 class="vcf-section-title vcf-section-title-line">Upcoming Tournaments & Matchday</h2>
        <p class="vcf-section-desc">Track our teams' progress as they compete in Houston's premier youth leagues. Check schedules, field locations, and results here.</p>

        <?php if (count($juegosPorTorneo) > 0): ?>
            <?php foreach ($juegosPorTorneo as $tid => $bloque): ?>
                <h3 class="vcf-torneo-title mt-4 mb-2"><?= htmlspecialchars($bloque['nombre_torneo']) ?><?= $bloque['temporada'] ? ' — ' . htmlspecialchars($bloque['temporada']) : '' ?></h3>
                <div class="vcf-table-wrap">
                    <table class="vcf-table">
                        <thead>
                            <tr>
                                <th>Day &amp; Date</th>
                                <th>Time</th>
                                <th>Opponent</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloque['juegos'] as $j): ?>
                                <?php
                                $lugar = ($j['sede_nombre'] && $j['cancha']) ? $j['sede_nombre'] . ' - ' . $j['cancha'] : ($j['cancha'] ?? $j['sede_nombre'] ?? '—');
                                $gameTs = strtotime($j['fecha'] . ' ' . (!empty($j['hora']) ? $j['hora'] : '23:59:59'));
                                $isPast = $gameTs < time();
                                if ($isPast) {
                                    $estado = 'finalizado';
                                    $canAddCalendar = false;
                                } else {
                                    $estado = $j['estado'] ?? 'proximo';
                                    $canAddCalendar = ($estado === 'proximo' || $estado === 'live');
                                }
                                ?>
                                <tr>
                                    <td><?= date('D, M j', strtotime($j['fecha'])) ?></td>
                                    <td><?= (!empty($j['hora'])) ? date('g:i A', strtotime($j['hora'])) : '—' ?></td>
                                    <td><?php if (!empty($j['rival'])): ?><span class="vcf-opponent"><?= htmlspecialchars($j['rival']) ?></span><?php else: ?>—<?php endif; ?></td>
                                    <td><?= htmlspecialchars($lugar) ?></td>
                                    <td>
                                        <?php if ($estado === 'live'): ?>
                                            <span class="vcf-badge-live">Live</span>
                                        <?php elseif ($estado === 'finalizado'): ?>
                                            <span class="vcf-badge-finalizado">Finished</span>
                                        <?php else: ?>
                                            <span class="vcf-badge-proximo">Upcoming</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($canAddCalendar): ?>
                                            <a href="<?= htmlspecialchars($base ?? '') ?>/calendar.php?id=<?= (int) $j['id'] ?>" class="vcf-btn-calendar" target="_blank" rel="noopener noreferrer" title="Add to my calendar">Add to calendar</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (count($topScorers) > 0): ?>
        <div class="mt-4 mb-4">
            <h3 class="stats-title">Top Scorers <span class="vcf-accent">(Pichichi)</span></h3>
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
            <div class="vcf-table-wrap">
                <table class="vcf-table vcf-table-results">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Local</th>
                            <th>Score</th>
                            <th>Visitante</th>
                            <th>Info</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosResultados as $r): ?>
                            <?php
                            $gv = (int) ($r['goles_vcf'] ?? 0);
                            $gr = (int) ($r['goles_rival'] ?? 0);
                            $hasScore = (isset($r['goles_vcf']) && $r['goles_vcf'] !== null) || (isset($r['goles_rival']) && $r['goles_rival'] !== null);
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
                                <td><?= date('M j', strtotime($r['fecha'])) ?></td>
                                <td>
                                    <span class="vcf-result-team">
                                        <?php if ($vcf_crest_file): ?>
                                            <img src="<?= $base ?? '' ?>/assets/img/<?= $vcf_crest_file ?>" alt="" class="vcf-team-logo" width="28" height="28" loading="lazy">
                                        <?php else: ?>
                                            <span class="vcf-team-logo vcf-team-logo-placeholder" aria-hidden="true"><i class="fas fa-shield"></i></span>
                                        <?php endif; ?>
                                        <span class="<?= $vcfClass ?>">VCF Houston</span>
                                    </span>
                                </td>
                                <td class="vcf-score-cell">
                                    <?php if ($hasScore): ?>
                                        <span class="<?= $scoreVcfClass ?>"><?= $gv ?></span> – <span class="<?= $scoreRivalClass ?>"><?= $gr ?></span>
                                    <?php else: ?>
                                        <span class="vcf-result-draw">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="vcf-result-team">
                                        <?php if ($rivalLogoUrl): ?>
                                            <img src="<?= htmlspecialchars($rivalLogoUrl) ?>" alt="" class="vcf-team-logo" width="28" height="28" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex';">
                                            <span class="vcf-team-logo vcf-team-logo-placeholder" style="display:none;" aria-hidden="true"><i class="fas fa-shield"></i></span>
                                        <?php else: ?>
                                            <span class="vcf-team-logo vcf-team-logo-placeholder" aria-hidden="true"><i class="fas fa-shield"></i></span>
                                        <?php endif; ?>
                                        <span class="<?= $rivalClass ?>"><?= $rivalName ?></span>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($resultOutcome === 'W'): ?>
                                        <span class="vcf-badge-w">W</span>
                                    <?php elseif ($resultOutcome === 'L'): ?>
                                        <span class="vcf-badge-l">L</span>
                                    <?php elseif ($resultOutcome === 'D'): ?>
                                        <span class="vcf-badge-d">D</span>
                                    <?php else: ?>
                                        <span class="vcf-result-draw">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (count($juegosPorTorneo) === 0): ?>
            <div class="vcf-empty-state">
                <i class="fas fa-futbol vcf-empty-state-icon" aria-hidden="true"></i>
                <p>No matches scheduled yet.</p>
            </div>
        <?php endif; ?>
    </div>
    </div>
</section>
