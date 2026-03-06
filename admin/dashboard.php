<?php
require __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/breadcrumb.php';
$page_title = 'Admin Dashboard - VCF Academy Houston';
require __DIR__ . '/../includes/header.php';
?>
<div class="container py-5">
    <?= admin_breadcrumb([]) ?>
    <h1 class="mb-4 admin-page-title">Admin Dashboard</h1>
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
    <div class="mt-4">
        <?php if (admin_can('change_own_password')): ?><a href="change-password.php" class="btn btn-outline-warning me-2">Change password</a><?php endif; ?>
        <a href="../index.php" class="btn btn-outline-secondary me-2">View site</a>
        <a href="logout.php" class="btn btn-outline-danger">Log out</a>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
