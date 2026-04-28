<?php
if (count($rosterPorCategoria) === 0) {
    return;
}

require_once __DIR__ . '/../roster_i18n.php';

$b = isset($base) ? $base : '';
$apiPlayer = htmlspecialchars($b . '/api/roster-player.php', ENT_QUOTES, 'UTF-8');
$crest = htmlspecialchars($b . '/assets/img/' . ($vcf_crest_file ?? 'vcf-crest.svg'), ENT_QUOTES, 'UTF-8');

$ids = [];
foreach ($rosterPorCategoria as $catData) {
    foreach ($catData['jugadores'] as $j) {
        $ids[] = (int) $j['id'];
    }
}

$astFromJg = [];
$motmMap = [];
if (!empty($ids) && isset($pdo)) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    try {
        $st = $pdo->prepare("SELECT roster_id, COALESCE(SUM(asistencias), 0) FROM juego_goles WHERE roster_id IN ($ph) GROUP BY roster_id");
        $st->execute($ids);
        while ($row = $st->fetch(PDO::FETCH_NUM)) {
            $astFromJg[(int) $row[0]] = (int) $row[1];
        }
    } catch (PDOException $e) {
    }
    try {
        $st = $pdo->prepare(
            'SELECT n.roster_id, COUNT(*) FROM motm_votaciones v INNER JOIN motm_nominees n ON n.id = v.winner_nominee_id WHERE n.roster_id IN (' . $ph . ') GROUP BY n.roster_id'
        );
        $st->execute($ids);
        while ($row = $st->fetch(PDO::FETCH_NUM)) {
            $motmMap[(int) $row[0]] = (int) $row[1];
        }
    } catch (PDOException $e) {
    }
}

$posToVrGroup = ['Portero' => 'GK', 'Defensa' => 'DEF', 'Mediocampista' => 'MID', 'Delantero' => 'FWD'];
$vr_season_y = (int) date('n') >= 8 ? (int) date('Y') : (int) date('Y') - 1;
$vr_season_lbl = $vr_season_y . '–' . substr((string) ($vr_season_y + 1), -2);

$vrList = [];
foreach ($rosterPorCategoria as $catData) {
    foreach ($catData['jugadores'] as $j) {
        $id = (int) $j['id'];
        $sub = !empty($rosterHasSubPosicion) ? ($j['sub_posicion'] ?? null) : null;
        $j['__cat'] = $catData['nombre'];
        $j['__goals'] = (int) ($j['total_goles'] ?? 0);
        $j['__assists'] = (int) ($astFromJg[$id] ?? 0);
        $j['__motm'] = (int) ($motmMap[$id] ?? 0);
        $j['__group'] = $posToVrGroup[$j['posicion'] ?? ''] ?? 'FWD';
        $j['__pos_short'] = vcf_roster_pos_short($j);
        $j['__pos_full'] = vcf_roster_position_en($j['posicion'] ?? null, $sub);
        $fullName = trim(($j['nombre'] ?? '') . ' ' . ($j['apellido'] ?? ''));
        if (function_exists('mb_substr')) {
            $j['__initials'] = mb_strtoupper(mb_substr(trim($j['nombre'] ?? ''), 0, 1)) . mb_strtoupper(mb_substr(trim($j['apellido'] ?? ''), 0, 1));
        } else {
            $j['__initials'] = strtoupper(substr(trim($j['nombre'] ?? ''), 0, 1)) . strtoupper(substr(trim($j['apellido'] ?? ''), 0, 1));
        }
        $j['__name_full'] = $fullName;
        $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        $j['__name_line'] = ($parts[0] ?? '') . (isset($parts[1]) ? ' ' . $parts[1] : '');
        $j['__star'] = false;
        if (!empty($jugadorMes) && isset($jugadorMes['dorsal'], $j['dorsal']) && (int) $jugadorMes['dorsal'] === (int) $j['dorsal']) {
            $j['__star'] = true;
        }
        $vrList[] = $j;
    }
}

$hasAnyStat = false;
foreach ($vrList as $v) {
    if ($v['__goals'] > 0 || $v['__assists'] > 0 || $v['__motm'] > 0) {
        $hasAnyStat = true;
        break;
    }
}

$boardRows = [];
foreach ($vrList as $v) {
    $boardRows[] = [
        'id' => (int) $v['id'],
        'name' => $v['__name_full'],
        'initials' => $v['__initials'],
        'photo' => $v['foto_url'] ?? '',
        'goals' => $v['__goals'],
        'assists' => $v['__assists'],
        'motm' => $v['__motm'],
    ];
}

$top3 = static function (array $rows, string $key): array {
    $c = $rows;
    usort($c, static function ($a, $b) use ($key) {
        return ($b[$key] ?? 0) <=> ($a[$key] ?? 0);
    });

    return array_slice($c, 0, 3);
};

$top_scorers = $top3($boardRows, 'goals');
$top_assists = $top3($boardRows, 'assists');
$top_motm = $top3($boardRows, 'motm');

$__firstRosterCat = reset($rosterPorCategoria);
$__rosterTitle = count($rosterPorCategoria) === 1
    ? ($__firstRosterCat['nombre'] ?? 'Roster')
    : 'Academy';
$multiCat = count($rosterPorCategoria) > 1;
$nPlayers = count($vrList);

$countGk = count(array_filter($vrList, static function ($x) { return $x['__group'] === 'GK'; }));
$countDef = count(array_filter($vrList, static function ($x) { return $x['__group'] === 'DEF'; }));
$countMid = count(array_filter($vrList, static function ($x) { return $x['__group'] === 'MID'; }));
$countFwd = count(array_filter($vrList, static function ($x) { return $x['__group'] === 'FWD'; }));
?>
<section id="roster" class="vcf-section--dark" data-base-url="<?= htmlspecialchars($b, ENT_QUOTES, 'UTF-8') ?>" data-player-api="<?= $apiPlayer ?>" data-crest-url="<?= $crest ?>">
    <div class="vcf-section__inner" style="padding-top:44px;padding-bottom:44px;border-bottom:1px solid var(--vcf-border);">
        <div class="vcf-section__header">
            <h2 class="vcf-section__title">Roster <em><?= htmlspecialchars($__rosterTitle) ?></em></h2>
            <span class="vr-roster-meta"><?= (int) $nPlayers ?> Players · Season <?= htmlspecialchars($vr_season_lbl) ?></span>
        </div>
        <p style="font-size:13px;color:var(--vcf-gray);margin-bottom:22px;max-width:520px;line-height:1.6;">Search, filter by line, or open a card for full stats and skills.</p>

        <?php if ($hasAnyStat): ?>
        <div class="vr-leaderboard">
            <?php
            $boards = [
                ['title' => 'Top Scorers', 'icon' => '⚽', 'data' => $top_scorers, 'stat' => 'goals', 'lbl' => 'goals'],
                ['title' => 'Top Assists', 'icon' => '🎯', 'data' => $top_assists, 'stat' => 'assists', 'lbl' => 'ast'],
                ['title' => 'Man of the Match', 'icon' => '⭐', 'data' => $top_motm, 'stat' => 'motm', 'lbl' => 'MOTM'],
            ];
            foreach ($boards as $bd):
            ?>
            <div class="vr-board">
                <div class="vr-board__title"><?= $bd['icon'] ?> <?= htmlspecialchars($bd['title']) ?></div>
                <?php foreach ($bd['data'] as $rank => $p): ?>
                <div class="vr-board__row<?= $rank === 0 ? ' top' : '' ?>">
                    <span class="vr-board__rank"><?= (int) $rank + 1 ?></span>
                    <div class="vr-board__av">
                        <?php if (!empty($p['photo'])): ?>
                            <img src="<?= htmlspecialchars($b . '/' . ltrim($p['photo'], '/')) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy" onerror="this.style.display='none';this.nextSibling.style.display='flex'">
                            <span style="display:none"><?= htmlspecialchars($p['initials']) ?></span>
                        <?php else: ?>
                            <?= htmlspecialchars($p['initials']) ?>
                        <?php endif; ?>
                    </div>
                    <?php
                    $bn = preg_split('/\s+/', $p['name'], -1, PREG_SPLIT_NO_EMPTY);
                    $bline = ($bn[0] ?? '') . (isset($bn[1]) ? ' ' . $bn[1] : '');
                    ?>
                    <span class="vr-board__name"><?= htmlspecialchars($bline) ?></span>
                    <span class="vr-board__val"><?= (int) ($p[$bd['stat']] ?? 0) ?></span>
                    <span class="vr-board__lbl"><?= htmlspecialchars($bd['lbl']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="vr-controls">
            <div class="vr-search-wrap">
                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" class="vr-search-icon" aria-hidden="true"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg>
                <input type="text" id="vr-search" class="vr-search" placeholder="Search player..." autocomplete="off" spellcheck="false" aria-label="Search players">
                <button type="button" id="vr-search-clear" class="vr-search-clear" style="display:none" aria-label="Clear search">×</button>
            </div>
            <div class="vr-filters" id="vr-filters">
                <button type="button" class="vr-filter active" data-group="all">All <span class="vr-filter__count"><?= (int) $nPlayers ?></span></button>
                <button type="button" class="vr-filter" data-group="GK">GK <span class="vr-filter__count"><?= (int) $countGk ?></span></button>
                <button type="button" class="vr-filter" data-group="DEF">DEF <span class="vr-filter__count"><?= (int) $countDef ?></span></button>
                <button type="button" class="vr-filter" data-group="MID">MID <span class="vr-filter__count"><?= (int) $countMid ?></span></button>
                <button type="button" class="vr-filter" data-group="FWD">FWD <span class="vr-filter__count"><?= (int) $countFwd ?></span></button>
            </div>
        </div>

        <div class="vr-grid" id="vr-grid">
            <?php foreach ($vrList as $i => $j): ?>
            <div class="vr-card roster-card-clickable"
                 role="button"
                 tabindex="0"
                 data-roster-id="<?= (int) $j['id'] ?>"
                 data-group="<?= htmlspecialchars($j['__group'], ENT_QUOTES, 'UTF-8') ?>"
                 data-name="<?= htmlspecialchars(strtolower($j['__name_full']), ENT_QUOTES, 'UTF-8') ?>"
                 aria-label="View <?= htmlspecialchars($j['__name_full'], ENT_QUOTES, 'UTF-8') ?> stats">
                <div class="vr-card__photo">
                    <?php if (!empty($j['foto_url'])): ?>
                        <img src="<?= htmlspecialchars($b . '/' . ltrim($j['foto_url'], '/')) ?>" alt="<?= htmlspecialchars($j['__name_full'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                        <div class="vr-card__initials" style="display:none"><?= htmlspecialchars($j['__initials']) ?></div>
                    <?php else: ?>
                        <div class="vr-card__initials"><?= htmlspecialchars($j['__initials']) ?></div>
                    <?php endif; ?>
                    <?php if ($j['dorsal'] !== null && $j['dorsal'] !== ''): ?>
                        <div class="vr-card__num"><?= (int) $j['dorsal'] ?></div>
                    <?php endif; ?>
                    <?php if (!empty($j['__star'])): ?>
                        <div class="vr-card__badge vr-card__badge--star">★ Star</div>
                    <?php endif; ?>
                    <?php if ($multiCat): ?>
                        <div class="vr-card__badge vr-card__badge--cat"><?= htmlspecialchars($j['__cat']) ?></div>
                    <?php endif; ?>
                    <div class="vr-card__overlay"><span>View Stats</span></div>
                </div>
                <div class="vr-card__bar">
                    <div class="vr-card__name"><?= htmlspecialchars($j['__name_line']) ?></div>
                    <div class="vr-card__pos"><?= htmlspecialchars($j['__pos_short']) ?><?= $j['__goals'] > 0 ? ' · ' . (int) $j['__goals'] . ' ⚽' : '' ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="vr-empty" id="vr-empty" style="display:none;">
                <div class="vr-empty__icon" aria-hidden="true">🔍</div>
                <div class="vr-empty__text">No players found</div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade player-modal" id="playerCardModal" tabindex="-1" aria-labelledby="playerCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content player-modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="player-modal-photo-wrap">
                            <img id="playerModalPhoto" src="" alt="" class="player-modal-photo">
                            <div id="playerModalPhotoPlaceholder" class="player-modal-photo-placeholder d-none"><span id="playerModalInitials"></span></div>
                            <div class="player-modal-watermark" aria-hidden="true"><img id="playerModalCrest" src="" alt="" style="width:80px;height:80px;opacity:0.15;pointer-events:none;"></div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h2 id="playerModalName" class="player-modal-name mb-1"></h2>
                        <p id="playerModalMeta" class="player-modal-meta small mb-3"></p>
                        <div class="player-modal-stats">
                            <div class="player-stat-row"><span class="player-stat-label">Apps</span><span class="player-stat-value" id="statApps">0</span><div class="player-stat-bar"><div class="player-stat-fill" id="barApps" style="width:0%"></div></div></div>
                            <div class="player-stat-row"><span class="player-stat-label">Goals</span><span class="player-stat-value" id="statGoals">0</span><div class="player-stat-bar"><div class="player-stat-fill" id="barGoals" style="width:0%"></div></div></div>
                            <div class="player-stat-row"><span class="player-stat-label">Assists</span><span class="player-stat-value" id="statAssists">0</span><div class="player-stat-bar"><div class="player-stat-fill" id="barAssists" style="width:0%"></div></div></div>
                            <div class="player-stat-row"><span class="player-stat-label">MOTM</span><span class="player-stat-value" id="statMotm">0</span><div class="player-stat-bar"><div class="player-stat-fill" id="barMotm" style="width:0%"></div></div></div>
                            <div class="player-stat-row"><span class="player-stat-label">Clean Sheets</span><span class="player-stat-value" id="statCS">0</span><div class="player-stat-bar"><div class="player-stat-fill" id="barCS" style="width:0%"></div></div></div>
                        </div>
                        <div id="playerModalRadarWrap" class="player-modal-radar-wrap mt-3 d-none">
                            <p class="small text-muted mb-1">Skills (1–10)</p>
                            <svg id="playerModalRadar" class="player-modal-radar" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <polygon id="playerRadarGrid" fill="none" stroke="rgba(255,255,255,0.15)" stroke-width="0.5" points="50,10 77,27.5 77,72.5 50,90 23,72.5 23,27.5"/>
                                <polygon id="playerRadarGrid2" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.3" points="50,22 65.5,31.25 65.5,68.75 50,78 34.5,68.75 34.5,31.25"/>
                                <line x1="50" y1="50" x2="50" y2="10" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <line x1="50" y1="50" x2="77" y2="27.5" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <line x1="50" y1="50" x2="77" y2="72.5" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <line x1="50" y1="50" x2="23" y2="72.5" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <line x1="50" y1="50" x2="23" y2="27.5" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <line x1="50" y1="50" x2="50" y2="90" stroke="rgba(255,255,255,0.2)" stroke-width="0.3"/>
                                <polygon id="playerRadarPolygon" fill="rgba(255,102,0,0.35)" stroke="rgba(255,102,0,0.9)" stroke-width="1" points=""/>
                                <text x="50" y="8" text-anchor="middle" class="player-radar-label" data-axis="0" fill="rgba(255,255,255,0.7)" font-size="5">Pace</text>
                                <text x="80" y="28" text-anchor="middle" class="player-radar-label" data-axis="1" fill="rgba(255,255,255,0.7)" font-size="5">Shoot</text>
                                <text x="80" y="74" text-anchor="middle" class="player-radar-label" data-axis="2" fill="rgba(255,255,255,0.7)" font-size="5">Drib</text>
                                <text x="50" y="93" text-anchor="middle" class="player-radar-label" data-axis="3" fill="rgba(255,255,255,0.7)" font-size="5">Def</text>
                                <text x="20" y="74" text-anchor="middle" class="player-radar-label" data-axis="4" fill="rgba(255,255,255,0.7)" font-size="5">Phys</text>
                                <text x="20" y="28" text-anchor="middle" class="player-radar-label" data-axis="5" fill="rgba(255,255,255,0.7)" font-size="5">Pass</text>
                            </svg>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-light btn-sm" id="playerModalShare"><i class="fas fa-share-alt me-1"></i> Share Player</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
