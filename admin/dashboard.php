<?php
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/csrf.php';      // needed for the logout form token
require_once __DIR__ . '/includes/breadcrumb.php';
require_once __DIR__ . '/../includes/motm_close_expired.php';

if (isset($pdo)) {
    vcf_motm_close_expired($pdo);
}

// ── KPI queries ──────────────────────────────────────────────
$kpi = [
    'next_match'    => null,
    'inscripciones' => 0,
    'mensajes'      => 0,
    'voto_activo'   => null,
];
try {
    $nowHouston = new DateTime('now', new DateTimeZone('America/Chicago'));
    $today      = $nowHouston->format('Y-m-d');
    $timeNow    = $nowHouston->format('H:i:s');

    $s = $pdo->prepare(
        "SELECT j.fecha, j.hora, j.rival FROM juegos j
         WHERE (j.fecha > ?) OR (j.fecha = ? AND (j.hora IS NULL OR j.hora > ?))
         ORDER BY j.fecha ASC, j.hora ASC LIMIT 1"
    );
    $s->execute([$today, $today, $timeNow]);
    $kpi['next_match'] = $s->fetch(PDO::FETCH_ASSOC);

    $s = $pdo->query("SELECT COUNT(*) FROM inscripciones WHERE created_at >= NOW() - INTERVAL 7 DAY");
    $kpi['inscripciones'] = (int) $s->fetchColumn();

    $s = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE created_at >= NOW() - INTERVAL 7 DAY");
    $kpi['mensajes'] = (int) $s->fetchColumn();

    try {
        $s = $pdo->query("SELECT 'MOTM' AS tipo, ends_at FROM motm_votaciones WHERE status='open' ORDER BY created_at DESC LIMIT 1");
        $kpi['voto_activo'] = $s->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
    if (!$kpi['voto_activo']) {
        try {
            $s = $pdo->query("SELECT 'Star' AS tipo, ends_at FROM star_votaciones WHERE status='open' ORDER BY created_at DESC LIMIT 1");
            $kpi['voto_activo'] = $s->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}
    }
} catch (PDOException $e) {
    error_log('Dashboard KPI: ' . $e->getMessage());
}

$page_title = 'Admin Dashboard - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([]) ?>
    <h1 class="mb-4 admin-page-title">Admin Dashboard</h1>

    <?php if (isset($_GET['cache_cleared'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Public page cache cleared (<?= (int) $_GET['cache_cleared'] ?> file(s) removed). Visitors now see the latest content.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php elseif (($_GET['cache_err'] ?? '') === 'csrf'): ?>
    <div class="alert alert-warning" role="alert">Could not clear cache: invalid security token. Try again.</div>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card bg-dark border border-secondary rounded-3 h-100 text-center p-3">
                <div class="mb-1"><i class="fas fa-futbol fa-lg text-warning" aria-hidden="true"></i></div>
                <div class="small text-muted text-uppercase fw-bold mb-1" style="letter-spacing:.06em;font-size:10px;">Next Match</div>
                <?php if ($kpi['next_match']): ?>
                <div class="text-white fw-bold" style="font-size:13px;"><?= htmlspecialchars($kpi['next_match']['rival'] ?: 'TBD') ?></div>
                <div class="text-muted" style="font-size:11px;"><?= date('M j', strtotime($kpi['next_match']['fecha'])) ?><?= $kpi['next_match']['hora'] ? ' · ' . date('g:i A', strtotime($kpi['next_match']['hora'])) : '' ?></div>
                <?php else: ?>
                <div class="text-muted small">No upcoming</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-dark border border-secondary rounded-3 h-100 text-center p-3">
                <div class="mb-1"><i class="fas fa-user-plus fa-lg text-success" aria-hidden="true"></i></div>
                <div class="small text-muted text-uppercase fw-bold mb-1" style="letter-spacing:.06em;font-size:10px;">New Registrations</div>
                <div class="text-white fw-bold" style="font-size:24px;"><?= $kpi['inscripciones'] ?></div>
                <div class="text-muted" style="font-size:11px;">last 7 days</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-dark border border-secondary rounded-3 h-100 text-center p-3">
                <div class="mb-1"><i class="fas fa-envelope fa-lg text-info" aria-hidden="true"></i></div>
                <div class="small text-muted text-uppercase fw-bold mb-1" style="letter-spacing:.06em;font-size:10px;">Contact Messages</div>
                <div class="text-white fw-bold" style="font-size:24px;"><?= $kpi['mensajes'] ?></div>
                <div class="text-muted" style="font-size:11px;">last 7 days</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-dark border border-secondary rounded-3 h-100 text-center p-3">
                <div class="mb-1"><i class="fas fa-vote-yea fa-lg" style="color:var(--vcf-orange,#E87722)" aria-hidden="true"></i></div>
                <div class="small text-muted text-uppercase fw-bold mb-1" style="letter-spacing:.06em;font-size:10px;">Active Vote</div>
                <?php if ($kpi['voto_activo']): ?>
                <div class="fw-bold" style="font-size:13px;color:var(--vcf-orange,#E87722);"><?= htmlspecialchars($kpi['voto_activo']['tipo']) ?> Open</div>
                <div class="text-muted" style="font-size:11px;">Ends <?= date('M j g:i A', strtotime($kpi['voto_activo']['ends_at'])) ?></div>
                <?php else: ?>
                <div class="text-muted small">No active vote</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'forbidden'): ?>
    <div class="alert alert-danger py-2 mb-3">You do not have permission to access that page.</div>
    <?php endif; ?>
    <?php if (isset($_GET['seeded']) && $_GET['seeded'] === '1'): ?>
    <div class="alert alert-success py-2 mb-3">Demo data loaded. Reload the <a href="../index.php">homepage</a> to see Latest Results, Next Match, MOTM, and player stats/radar.</div>
    <?php endif; ?>
    <?php if (isset($_GET['rollback']) && $_GET['rollback'] === '1'): ?>
    <div class="alert alert-info py-2 mb-3">Demo data removed. Your games and data are as before.</div>
    <?php endif; ?>

    <p class="text-muted mb-4">Role: <strong><?= htmlspecialchars($admin_role) ?></strong>. Manage content for the VCF Academy Houston landing page.</p>

    <div class="row g-3">
        <?php if (admin_can('hero_slider')): ?>
        <div class="col-md-4">
            <a href="hero-slider.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-images fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Hero Slider</h5>
                        <p class="card-text text-muted small">Banners full-width under the header. Images, titles, and buttons.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (admin_can('match_reels')): ?>
        <div class="col-md-4">
            <a href="match-reels.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-video fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Match Reels</h5>
                        <p class="card-text text-muted small">Upload and manage goal clips (reels). Show below Latest Results.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (admin_can('jugador_mes')): ?>
        <div class="col-md-4">
            <a href="jugador-mes.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-trophy fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Jugador del Mes</h5>
                        <p class="card-text text-muted small">Update photo and details for Star of the Month.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (admin_can('sedes')): ?>
        <div class="col-md-4">
            <a href="sedes.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Sedes / Grounds</h5>
                        <p class="card-text text-muted small">Add, edit, or remove training locations.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (admin_can('torneos')): ?>
        <div class="col-md-4">
            <a href="torneos.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Torneos</h5>
                        <p class="card-text text-muted small">Manage tournaments and matchday schedule.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (admin_can('noticias')): ?>
        <div class="col-md-4">
            <a href="noticias.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="far fa-newspaper fa-2x mb-2"></i>
                        <h5 class="card-title text-white">News</h5>
                        <p class="card-text text-muted small">Publish articles, match recaps and academy updates.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (admin_can('categorias')): ?>
        <div class="col-md-4">
            <a href="categorias.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Categories</h5>
                        <p class="card-text text-muted small">Manage age groups and training schedules.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (admin_can('roster_edit')): ?>
        <div class="col-md-4">
            <a href="roster.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-id-card fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Roster / Plantilla</h5>
                        <p class="card-text text-muted small">Manage players by category for MOTM and lineup.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (admin_can('motm')): ?>
        <div class="col-md-4">
            <a href="motm.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-trophy fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Man of the Match</h5>
                        <p class="card-text text-muted small">Start 2-hour voting and view results.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (admin_can('juegos') || admin_can('juegos_live_score')): ?>
        <div class="col-md-4">
            <a href="torneos.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-futbol fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Juegos / Partidos</h5>
                        <p class="card-text text-muted small">Results, scorers, live score. Open from Torneos.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <?php if (admin_can('inscripciones_view')): ?>
        <div class="col-md-4">
            <a href="inscripciones.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-user-plus fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Inscripciones</h5>
                        <p class="card-text text-muted small">View registration form submissions.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (admin_can('contact_messages_view')): ?>
        <div class="col-md-4">
            <a href="contact-messages.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-envelope fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Contact Messages</h5>
                        <p class="card-text text-muted small">Read messages sent through the contact form.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <?php if (admin_can('support_donations')): ?>
        <div class="col-md-4">
            <a href="support-donations.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-hand-holding-heart fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Support Donations</h5>
                        <p class="card-text text-muted small">Edit parent donations shown on the support page.</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>

        <?php if (admin_can('*')): ?>
        <div class="col-md-4">
            <a href="users.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-user-shield fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Users &amp; Roles</h5>
                        <p class="card-text text-muted small">Invite staff, assign roles (Super Admin, Editor, Staff).</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="activity-log.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-history fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Activity Log</h5>
                        <p class="card-text text-muted small">See who did what (scores, MOTM, roster changes).</p>
                    </div>
                </div>
            </a>
        </div>
        <?php elseif (admin_can('activity_log_view')): ?>
        <div class="col-md-4">
            <a href="activity-log.php" class="text-decoration-none">
                <div class="card bg-dark border border-secondary border-2 rounded-3 h-100 admin-card hover-orange">
                    <div class="card-body">
                        <i class="fas fa-history fa-2x mb-2"></i>
                        <h5 class="card-title text-white">Activity Log</h5>
                        <p class="card-text text-muted small">See who did what (scores, MOTM, roster changes).</p>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $adminDir   = rtrim(dirname($scriptName), '/');
    $host       = $_SERVER['HTTP_HOST'] ?? '';
    $scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $staffLoginUrl = $host !== '' ? ($scheme . '://' . $host . $adminDir . '/') : ($adminDir . '/');
    ?>
    <div class="card bg-dark border border-secondary border-2 rounded-3 mt-4">
        <div class="card-body">
            <h2 class="h5 text-white mb-2"><i class="fas fa-sign-in-alt me-2 text-warning" aria-hidden="true"></i> Staff login (Acceder)</h2>
            <p class="text-muted small mb-3 mb-md-2">Bookmark or share this URL only with staff. It opens the admin login page.</p>
            <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2">
                <code class="flex-grow-1 text-white bg-black border border-secondary rounded px-3 py-2 small mb-0 user-select-all text-break"><?= htmlspecialchars($staffLoginUrl) ?></code>
                <a href="index.php" class="btn btn-outline-warning text-nowrap">Open login page</a>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex flex-wrap gap-2">
        <?php if (admin_can('change_own_password')): ?>
        <a href="change-password.php" class="btn btn-outline-warning">Change password</a>
        <?php endif; ?>
        <a href="../index.php" class="btn btn-outline-secondary">View site</a>
        <!-- Clear public page cache so saved changes show up instantly -->
        <form method="post" action="clear-cache.php" class="d-inline"
              onsubmit="return confirm('Clear the public page cache now?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-info" title="Force visitors to see the latest content immediately (bypass the 60s cache).">
                <i class="fas fa-broom me-1" aria-hidden="true"></i>Clear cache
            </button>
        </form>
        <!-- Logout is POST to prevent CSRF logout via GET links / <img> tags -->
        <form method="post" action="logout.php" class="d-inline"
              onsubmit="return confirm('¿Cerrar sesión?');">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger">Log out</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
