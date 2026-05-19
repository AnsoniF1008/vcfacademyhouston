<?php
/**
 * Partial: tabla de juegos de un torneo.
 * Scope: $torneo (con clave 'juegos'), opcional $base.
 */

if (!isset($torneo) || empty($torneo['juegos'])) {
    echo '<div class="empty-games">No games scheduled.</div>';
    return;
}

$_tt_base = $base ?? '';
$_tt_now = new DateTime('now', new DateTimeZone('America/Chicago'));
$_tt_today = $_tt_now->format('Y-m-d');
$_tt_time  = $_tt_now->format('H:i:s');
?>
<div class="tournament-table-wrapper">
    <table class="tournament-table">
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
        <?php foreach ($torneo['juegos'] as $juego): ?>
            <?php
                $_tt_fecha = $juego['fecha'] ?? '';
                $_tt_hora  = $juego['hora']  ?? null;

                $_tt_is_past = false;
                if ($_tt_fecha < $_tt_today) {
                    $_tt_is_past = true;
                } elseif ($_tt_fecha === $_tt_today && $_tt_hora && $_tt_hora < $_tt_time) {
                    $_tt_is_past = true;
                }

                $_tt_estado = strtolower($juego['estado'] ?? '');
                if ($_tt_estado === '') {
                    $_tt_estado = $_tt_is_past ? 'finished' : 'upcoming';
                }

                $_tt_status_class = match ($_tt_estado) {
                    'finished', 'finalizado'       => 'status-finished',
                    'live', 'en vivo'              => 'status-live',
                    'cancelled', 'cancelado'       => 'status-cancelled',
                    'postponed', 'pospuesto'       => 'status-postponed',
                    default                        => 'status-upcoming',
                };

                $_tt_status_label = match ($_tt_estado) {
                    'finished', 'finalizado'       => 'Finished',
                    'live', 'en vivo'              => 'Live',
                    'cancelled', 'cancelado'       => 'Cancelled',
                    'postponed', 'pospuesto'       => 'Postponed',
                    default                        => 'Upcoming',
                };

                $_tt_dia_fecha = $_tt_fecha
                    ? date('D, M j', strtotime($_tt_fecha))
                    : '—';

                $_tt_hora_fmt = $_tt_hora
                    ? date('g:i A', strtotime($_tt_hora))
                    : 'TBD';

                $_tt_location = trim(
                    ($juego['sede_nombre'] ?? '') .
                    (!empty($juego['cancha']) ? ' — Field ' . $juego['cancha'] : '')
                );
                if ($_tt_location === '') {
                    $_tt_location = 'TBD';
                }
            ?>
            <tr class="game-row <?= $_tt_is_past ? 'past-game' : 'upcoming-game' ?>">
                <td class="game-date" data-label="Day &amp; Date"><?= htmlspecialchars($_tt_dia_fecha) ?></td>
                <td class="game-time" data-label="Time"><?= htmlspecialchars($_tt_hora_fmt) ?></td>
                <td class="game-opponent" data-label="Opponent">
                    <?= htmlspecialchars($juego['rival'] ?? 'TBD') ?>
                </td>
                <td class="game-location" data-label="Location">
                    <?php if (!empty($juego['ubicacion_mapa_url'])): ?>
                        <a href="<?= htmlspecialchars($juego['ubicacion_mapa_url']) ?>"
                           target="_blank" rel="noopener"
                           class="location-link">
                            <?= htmlspecialchars($_tt_location) ?>
                        </a>
                    <?php else: ?>
                        <?= htmlspecialchars($_tt_location) ?>
                    <?php endif; ?>
                </td>
                <td class="game-status" data-label="Status">
                    <span class="status-badge <?= $_tt_status_class ?>">
                        <?= htmlspecialchars($_tt_status_label) ?>
                    </span>
                </td>
                <td class="game-actions" data-label="">
                    <a href="<?= htmlspecialchars($_tt_base) ?>/match.php?id=<?= (int) $juego['id'] ?>"
                       class="btn-details">Details</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
