<?php
/**
 * Returns JSON for a roster player: info + stats + skills (for player card modal).
 * GET ?id=roster_id
 */
header('Content-Type: application/json');
header('Cache-Control: no-store');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid']);
    exit;
}

require __DIR__ . '/../config/database.php';

$hasSubPosicion = false;
try {
    $st = $pdo->query("SHOW COLUMNS FROM roster LIKE 'sub_posicion'");
    $hasSubPosicion = $st && $st->fetch();
} catch (PDOException $e) {
}

$rosterCols = 'r.id, r.nombre, r.apellido, r.dorsal, r.posicion, r.foto_url, c.nombre AS categoria_nombre';
if ($hasSubPosicion) {
    $rosterCols = 'r.id, r.nombre, r.apellido, r.dorsal, r.posicion, r.sub_posicion, r.foto_url, c.nombre AS categoria_nombre';
}

try {
    $stmt = $pdo->prepare("
        SELECT $rosterCols
        FROM roster r
        JOIN categorias c ON c.id = r.categoria_id
        WHERE r.id = ? AND r.activo = 1
    ");
    $stmt->execute([$id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$player) {
        http_response_code(404);
        echo json_encode(['error' => 'not_found']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT partidos_jugados, goles, asistencias, motm, clean_sheets FROM roster_estadisticas WHERE roster_id = ?");
    $stmt->execute([$id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$stats) {
        $stats = ['partidos_jugados' => 0, 'goles' => 0, 'asistencias' => 0, 'motm' => 0, 'clean_sheets' => 0];
    }
    // Override goles/asistencias from aggregate (juego_goles)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(goles), 0), COALESCE(SUM(asistencias), 0) FROM juego_goles WHERE roster_id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $stats['goles'] = (int) ($row[0] ?? 0);
    $stats['asistencias'] = (int) ($row[1] ?? 0);
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT juego_id) FROM juego_goles WHERE roster_id = ?");
    $stmt->execute([$id]);
    $partidosFromScorers = (int) $stmt->fetchColumn();
    if ($partidosFromScorers > (int) $stats['partidos_jugados']) {
        $stats['partidos_jugados'] = $partidosFromScorers;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM motm_votaciones v JOIN motm_nominees n ON n.id = v.winner_nominee_id WHERE n.roster_id = ?");
    $stmt->execute([$id]);
    $motmCount = (int) $stmt->fetchColumn();
    $stats['motm'] = $motmCount;

    $stmt = $pdo->prepare("SELECT pace, shooting, passing, dribbling, defense, physical FROM roster_habilidades WHERE roster_id = ?");
    $stmt->execute([$id]);
    $skills = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$skills) {
        $skills = ['pace' => 5, 'shooting' => 5, 'passing' => 5, 'dribbling' => 5, 'defense' => 5, 'physical' => 5];
    }

    echo json_encode([
        'player' => $player,
        'stats' => $stats,
        'skills' => $skills,
    ]);
} catch (PDOException $e) {
    error_log('roster-player API: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'error']);
}
