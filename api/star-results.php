<?php
/**
 * Star of the Month results for live bar chart. GET: votacion_id.
 * Returns JSON: [{ "id", "nombre", "foto_url", "categoria", "total_votes" }, ...] ordered by votes DESC.
 *
 * Caches the JSON payload on disk for a short window (10s) so concurrent
 * polling clients don't all hammer MySQL — with the front-end polling
 * every 30s, this brings the worst-case DB hit rate down to 6/min per
 * vote regardless of how many viewers have the page open.
 */
header('Content-Type: application/json');
header('Cache-Control: no-store, max-age=0');

$votacion_id = isset($_GET['votacion_id']) ? (int) $_GET['votacion_id'] : 0;
if ($votacion_id <= 0) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

$cacheDir  = __DIR__ . '/../cache/api';
$cacheFile = $cacheDir . '/star_results_' . $votacion_id . '.json';
$cacheTtl  = 10; // seconds
if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    echo file_get_contents($cacheFile);
    exit;
}

require __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->prepare("SELECT id, status FROM star_votaciones WHERE id = ?");
    $stmt->execute([$votacion_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT n.id, n.nombre, n.foto_url, n.categoria,
            COALESCE(COUNT(v.id), 0) AS total_votes
            FROM star_nominees n
            LEFT JOIN star_votes v ON v.nominee_id = n.id AND v.votacion_id = n.votacion_id
            WHERE n.votacion_id = ?
            GROUP BY n.id, n.nombre, n.foto_url, n.categoria
            ORDER BY total_votes DESC, n.orden ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$votacion_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as &$r) {
        $r['total_votes'] = (int) $r['total_votes'];
    }
    unset($r);

    $json = json_encode($results);

    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
    }
    @file_put_contents($cacheFile, $json, LOCK_EX);

    echo $json;
} catch (PDOException $e) {
    error_log('Star results: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([]);
}
