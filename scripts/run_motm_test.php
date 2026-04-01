<?php
/**
 * MOTM test: creates one closed votation with 3 nominees and fake votes,
 * so the admin table and the homepage show a winner.
 * Run once: php scripts/run_motm_test.php
 */
require dirname(__DIR__) . '/config/database.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Need: 1 game, 3 roster players
$juego = $pdo->query("SELECT id FROM juegos ORDER BY fecha DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$roster = $pdo->query("SELECT id, nombre, apellido, foto_url FROM roster WHERE activo = 1 ORDER BY id ASC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

if (!$juego || count($roster) < 3) {
    echo "Need at least 1 game and 3 active roster players. Add games (Admin > Juegos) and roster (Admin > Roster).\n";
    exit(1);
}

$juego_id = (int) $juego['id'];
$starts_at = date('Y-m-d H:i:s', strtotime('-3 hours'));
$ends_at = date('Y-m-d H:i:s', strtotime('-1 hour')); // already ended

$pdo->beginTransaction();
try {
    $pdo->prepare("INSERT INTO motm_votaciones (juego_id, starts_at, ends_at, status) VALUES (?, ?, ?, 'open')")
        ->execute([$juego_id, $starts_at, $ends_at]);
    $votacion_id = (int) $pdo->lastInsertId();

    $ins = $pdo->prepare("INSERT INTO motm_nominees (votacion_id, nombre, foto_url, orden, roster_id) VALUES (?, ?, ?, ?, ?)");
    $nominee_ids = [];
    foreach ($roster as $i => $r) {
        $nombre = trim($r['nombre'] . ' ' . $r['apellido']);
        $ins->execute([$votacion_id, $nombre, $r['foto_url'] ?? '', $i + 1, (int) $r['id']]);
        $nominee_ids[] = (int) $pdo->lastInsertId();
    }

    // Give most votes to nominee 2 (index 1) so they win: 5 for #2, 2 for #1, 1 for #3
    $winner_nominee_id = $nominee_ids[1];
    $voteIns = $pdo->prepare("INSERT INTO motm_votes (votacion_id, nominee_id, ip_address) VALUES (?, ?, ?)");
    $votes = [
        [$nominee_ids[1], '192.168.1.1'],
        [$nominee_ids[1], '192.168.1.2'],
        [$nominee_ids[1], '10.0.0.1'],
        [$nominee_ids[1], '10.0.0.2'],
        [$nominee_ids[1], '10.0.0.3'],
        [$nominee_ids[0], '172.16.0.1'],
        [$nominee_ids[0], '172.16.0.2'],
        [$nominee_ids[2], '172.16.0.3'],
    ];
    foreach ($votes as $v) {
        $voteIns->execute([$votacion_id, $v[0], $v[1]]);
    }

    // Close and set winner (same logic as index.php)
    $pdo->prepare("UPDATE motm_votaciones SET status = 'closed', winner_nominee_id = ? WHERE id = ?")
        ->execute([$winner_nominee_id, $votacion_id]);

    $pdo->commit();

    $winnerName = $pdo->prepare("SELECT nombre FROM motm_nominees WHERE id = ?");
    $winnerName->execute([$winner_nominee_id]);
    $name = $winnerName->fetchColumn();
    echo "MOTM test created: votation #{$votacion_id} for game #{$juego_id}, winner: {$name}.\n";
    echo "Open Admin > MOTM to see the votation and Winner column. Open the homepage to see the MOTM winner card.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
