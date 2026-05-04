<?php
/**
 * Public JSON API: match reel view_count / like_count persisted in MySQL.
 *
 * POST JSON body:
 *   { "action": "state"|"view"|"like_toggle", "fingerprint": "<16-128 chars>", "reel_id": int, "reel_ids": [int,...] }
 *
 * Requires sql/migrate_match_reels_stats.sql applied once.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method']);
    exit;
}

require __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/rate_limit.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw ?: '[]', true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_json']);
    exit;
}

$action = trim((string) ($input['action'] ?? ''));
$fingerprint = trim((string) ($input['fingerprint'] ?? ''));

function reel_stats_client_ip(): string
{
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }
    if (strlen($ip) > 45) {
        $ip = substr($ip, 0, 45);
    }
    return $ip !== '' ? $ip : '0.0.0.0';
}

function reel_stats_fp_hash(string $fp): ?string
{
    $len = strlen($fp);
    if ($len < 16 || $len > 128) {
        return null;
    }
    return hash('sha256', $fp);
}

$ip = reel_stats_client_ip();
if (!vcf_rate_limit_check('reel-stats-api', $ip, 120, 60)) {
    http_response_code(429);
    header('Retry-After: 60');
    echo json_encode(['error' => 'rate_limited']);
    exit;
}

$fh = reel_stats_fp_hash($fingerprint);
if ($fh === null) {
    http_response_code(400);
    echo json_encode(['error' => 'fingerprint']);
    exit;
}

try {
    switch ($action) {
        case 'state':
            $ids = $input['reel_ids'] ?? [];
            if (!is_array($ids)) {
                $ids = [];
            }
            $clean = [];
            foreach ($ids as $v) {
                $n = (int) $v;
                if ($n > 0) {
                    $clean[$n] = true;
                }
            }
            $ids = array_keys($clean);
            if ($ids === []) {
                echo json_encode(['reels' => new stdClass()]);
                exit;
            }
            sort($ids, SORT_NUMERIC);
            if (count($ids) > 80) {
                $ids = array_slice($ids, 0, 80);
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare(
                "SELECT id, view_count, like_count FROM match_reels WHERE id IN ($placeholders)"
            );
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $likedStmt = $pdo->prepare(
                'SELECT 1 FROM match_reel_like_visitors WHERE reel_id = ? AND fingerprint_hash = ? LIMIT 1'
            );

            $out = [];
            foreach ($rows as $row) {
                $rid = (int) $row['id'];
                $likedStmt->execute([$rid, $fh]);
                $liked = (bool) $likedStmt->fetchColumn();
                $out[(string) $rid] = [
                    'view_count' => (int) $row['view_count'],
                    'like_count' => (int) $row['like_count'],
                    'liked' => $liked,
                ];
            }
            echo json_encode(['reels' => $out]);
            exit;

        case 'view':
            $reelId = (int) ($input['reel_id'] ?? 0);
            if ($reelId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'reel_id']);
                exit;
            }

            $stmt = $pdo->prepare('SELECT view_count FROM match_reels WHERE id = ?');
            $stmt->execute([$reelId]);
            $vc = $stmt->fetchColumn();
            if ($vc === false) {
                http_response_code(404);
                echo json_encode(['error' => 'not_found']);
                exit;
            }

            $allowed = vcf_rate_limit_check('reelview-' . $reelId, $fh, 1, 86400);
            if ($allowed) {
                $pdo->prepare('UPDATE match_reels SET view_count = view_count + 1 WHERE id = ?')->execute([$reelId]);
                $stmt->execute([$reelId]);
                $vc = $stmt->fetchColumn();
            }

            echo json_encode(['view_count' => (int) $vc]);
            exit;

        case 'like_toggle':
            $reelId = (int) ($input['reel_id'] ?? 0);
            if ($reelId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'reel_id']);
                exit;
            }

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('SELECT id FROM match_reels WHERE id = ? FOR UPDATE');
                $stmt->execute([$reelId]);
                if (!$stmt->fetchColumn()) {
                    $pdo->rollBack();
                    http_response_code(404);
                    echo json_encode(['error' => 'not_found']);
                    exit;
                }

                $chk = $pdo->prepare(
                    'SELECT 1 FROM match_reel_like_visitors WHERE reel_id = ? AND fingerprint_hash = ? LIMIT 1 FOR UPDATE'
                );
                $chk->execute([$reelId, $fh]);
                $hadLike = (bool) $chk->fetchColumn();

                if ($hadLike) {
                    $pdo->prepare(
                        'DELETE FROM match_reel_like_visitors WHERE reel_id = ? AND fingerprint_hash = ?'
                    )->execute([$reelId, $fh]);
                    $pdo->prepare(
                        'UPDATE match_reels SET like_count = IF(like_count > 0, like_count - 1, 0) WHERE id = ?'
                    )->execute([$reelId]);
                    $liked = false;
                } else {
                    try {
                        $pdo->prepare(
                            'INSERT INTO match_reel_like_visitors (reel_id, fingerprint_hash) VALUES (?, ?)'
                        )->execute([$reelId, $fh]);
                        $pdo->prepare(
                            'UPDATE match_reels SET like_count = like_count + 1 WHERE id = ?'
                        )->execute([$reelId]);
                        $liked = true;
                    } catch (PDOException $ins) {
                        $dup = strpos($ins->getMessage(), 'Duplicate') !== false;
                        $code = (string) $ins->getCode();
                        if (!$dup && $code !== '23000') {
                            throw $ins;
                        }
                        // Concurrent insert won elsewhere — row exists; treat as liked, don't bump counter again.
                        $liked = true;
                    }
                }

                $pdo->commit();

                $stmt = $pdo->prepare('SELECT like_count FROM match_reels WHERE id = ?');
                $stmt->execute([$reelId]);
                echo json_encode([
                    'like_count' => (int) $stmt->fetchColumn(),
                    'liked' => $liked,
                ]);
                exit;
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

        default:
            http_response_code(400);
            echo json_encode(['error' => 'action']);
            exit;
    }
} catch (PDOException $e) {
    error_log('reel-stats PDO: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'server']);
}
