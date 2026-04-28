<?php
/**
 * MOTM vote endpoint. POST: votacion_id, nominee_id, fingerprint (optional).
 * Returns JSON: { "success": true } or { "error": "already_voted" | "closed" | "invalid" }
 */
header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$votacion_id = (int) ($input['votacion_id'] ?? $_POST['votacion_id'] ?? 0);
$nominee_id = (int) ($input['nominee_id'] ?? $_POST['nominee_id'] ?? 0);
$fingerprint = trim($input['fingerprint'] ?? $_POST['fingerprint'] ?? '');
$fingerprint_hash = $fingerprint !== '' ? hash('sha256', $fingerprint) : null;

if ($votacion_id <= 0 || $nominee_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid']);
    exit;
}

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

require_once __DIR__ . '/../includes/rate_limit.php';
if (!vcf_rate_limit_check('motm-vote', $ip, 10, 60)) {
    http_response_code(429);
    header('Retry-After: 60');
    echo json_encode(['error' => 'rate_limited']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, status, ends_at FROM motm_votaciones WHERE id = ?");
    $stmt->execute([$votacion_id]);
    $v = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$v) {
        http_response_code(404);
        echo json_encode(['error' => 'invalid']);
        exit;
    }
    if ($v['status'] !== 'open' || strtotime($v['ends_at']) <= time()) {
        echo json_encode(['error' => 'closed']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM motm_nominees WHERE id = ? AND votacion_id = ?");
    $stmt->execute([$nominee_id, $votacion_id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM motm_votes WHERE votacion_id = ? AND (ip_address = ? OR (fingerprint_hash IS NOT NULL AND fingerprint_hash = ?))");
    $stmt->execute([$votacion_id, $ip, $fingerprint_hash]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'already_voted']);
        exit;
    }

    $pdo->prepare("INSERT INTO motm_votes (votacion_id, nominee_id, ip_address, fingerprint_hash) VALUES (?, ?, ?, ?)")->execute([$votacion_id, $nominee_id, $ip, $fingerprint_hash]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['error' => 'already_voted']);
        exit;
    }
    error_log('MOTM vote: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'invalid']);
}
