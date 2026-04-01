<section id="grounds" class="vcf-section vcf-redesign-legacy">
    <div class="container my-5">
        <h2 class="vcf-section-title vcf-section-title-line">Training Grounds</h2>
        <p class="vcf-section-desc">We bring the Mestalla experience to your neighborhood. Find our official training locations across the Houston area, equipped with top-tier facilities for our youth categories.</p>
        <p class="vcf-section-desc mb-4">Houston is big, but we make it easy to find us. Check the specific field number for your kid's category below.</p>
        <?php if (count($sedes) > 0): ?>
        <div class="accordion" id="accordionSedes">
            <?php foreach ($sedes as $i => $sede): ?>
            <?php
                $sid = (int) $sede['id'];
                $canchas = $canchasBySede[$sid] ?? [];
                $accordionId = 'sede-' . $sid;
                $isFirst = ($i === 0);
            ?>
            <div class="accordion-item mb-3">
                <h2 class="accordion-header vcf-accordion-header">
                    <button class="accordion-button <?= $isFirst ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $accordionId ?>" aria-expanded="<?= $isFirst ? 'true' : 'false' ?>" aria-controls="<?= $accordionId ?>">
                        <strong class="text-uppercase"><?= htmlspecialchars($sede['nombre']) ?></strong>
                        <span class="ms-3 vcf-accordion-address">— <?= htmlspecialchars($sede['direccion']) ?></span>
                    </button>
                    <?php if (!empty($sede['mapa_general_url'])): ?>
                    <a href="<?= htmlspecialchars($sede['mapa_general_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm vcf-btn-gps vcf-accordion-gps">Open GPS</a>
                    <?php endif; ?>
                </h2>
                <div id="<?= $accordionId ?>" class="accordion-collapse collapse <?= $isFirst ? 'show' : '' ?>" data-bs-parent="#accordionSedes">
                    <div class="accordion-body vcf-accordion-body">
                        <?php if (!empty($sede['nota_acceso'])): ?>
                        <p class="text-muted mb-3"><i class="fas fa-info-circle me-2" style="color: var(--vcf-orange);"></i><?= htmlspecialchars($sede['nota_acceso']) ?></p>
                        <?php endif; ?>
                        <?php if (count($canchas) > 0): ?>
                        <ul class="list-group list-group-flush vcf-list-canchas">
                            <?php foreach ($canchas as $c): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center vcf-cancha-item">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-futbol me-2 vcf-cancha-icon" aria-hidden="true"></i>
                                    <div>
                                        <strong><?= htmlspecialchars($c['numero_cancha']) ?><?= !empty($c['sobrenombre']) ? ' <span class="vcf-sobrenombre">(' . htmlspecialchars($c['sobrenombre']) . ')</span>' : '' ?></strong>
                                        <?php if (!empty($c['indicaciones_extra'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($c['indicaciones_extra']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($c['mapa_url'])): ?>
                                <a href="<?= htmlspecialchars($c['mapa_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm vcf-btn-gps">Open GPS</a>
                                <?php else: ?>
                                <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-muted small mb-0"><i class="fas fa-info-circle me-2" style="color: var(--vcf-orange);"></i>No specific fields listed yet. Use the main entrance GPS above.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="vcf-empty-state">
            <i class="fas fa-map-marker-alt vcf-empty-state-icon" aria-hidden="true"></i>
            <p>Training locations will be listed here soon.</p>
        </div>
        <?php endif; ?>
    </div>
</section>
