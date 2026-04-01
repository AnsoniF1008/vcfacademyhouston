<?php
/**
 * Star of the Month vote. POST: votacion_id, nominee_id.
 * One vote per IP per votation. Returns JSON: { "status": "success" } or { "status": "error", "message": "..." }
 */
header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$votacion_id = (int) ($input['votacion_id'] ?? $_POST['votacion_id'] ?? 0);
$nominee_id = (int) ($input['nominee_id'] ?? $_POST['nominee_id'] ?? 0);

$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
if (strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
}
if (strlen($ip) > 45) {
    $ip = substr($ip, 0, 45);
}
if ($ip === '') {
    $ip = '0.0.0.0';
}

if ($votacion_id <= 0 || $nominee_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, status, ends_at FROM star_votaciones WHERE id = ?");
    $stmt->execute([$votacion_id]);
    $v = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$v) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Votación no encontrada.']);
        exit;
    }
    if ($v['status'] !== 'open') {
        echo json_encode(['status' => 'error', 'message' => 'La votación está cerrada.']);
        exit;
    }
    if (strtotime($v['ends_at']) <= time()) {
        echo json_encode(['status' => 'error', 'message' => 'La votación ha terminado.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM star_nominees WHERE id = ? AND votacion_id = ?");
    $stmt->execute([$nominee_id, $votacion_id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Nominado no válido.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM star_votes WHERE votacion_id = ? AND ip_address = ?");
    $stmt->execute([$votacion_id, $ip]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Ya has votado este mes.']);
        exit;
    }

    $pdo->prepare("INSERT INTO star_votes (votacion_id, nominee_id, ip_address) VALUES (?, ?, ?)")
        ->execute([$votacion_id, $nominee_id, $ip]);
    echo json_encode(['status' => 'success', 'message' => '¡Voto registrado!']);
} catch (PDOException $e) {
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        echo json_encode(['status' => 'error', 'message' => 'Ya has votado este mes.']);
        exit;
    }
    error_log('Star vote: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No se pudo registrar el voto.']);
}
