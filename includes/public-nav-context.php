<?php
/**
 * Shared nav data for public pages (MOTM, Star) when not already loaded by the page (e.g. index).
 * Skip entirely when $vcf_nav_context_loaded is true (index.php sets this after its queries).
 */
if (!empty($vcf_nav_context_loaded)) {
    return;
}
if (!isset($pdo)) {
    require_once __DIR__ . '/../config/database.php';
}

if (!isset($motmOpen) && !isset($motmWinner)) {
    $motmOpen = null;
    $motmWinner = null;
    try {
        $now = date('Y-m-d H:i:s');
        $stmtExp = $pdo->prepare("SELECT id FROM motm_votaciones WHERE status = 'open' AND ends_at <= ?");
        $stmtExp->execute([$now]);
        $expired = $stmtExp->fetchAll(PDO::FETCH_COLUMN);
        foreach ($expired as $vid) {
            $vid = (int) $vid;
            $top = $pdo->prepare("SELECT nominee_id FROM motm_votes WHERE votacion_id = ? GROUP BY nominee_id ORDER BY COUNT(*) DESC, nominee_id ASC LIMIT 1");
            $top->execute([$vid]);
            $winner_nominee_id = $top->fetchColumn();
            if ($winner_nominee_id) {
                $pdo->prepare("UPDATE motm_votaciones SET status = 'closed', winner_nominee_id = ? WHERE id = ?")->execute([$winner_nominee_id, $vid]);
            } else {
                $pdo->prepare("UPDATE motm_votaciones SET status = 'closed' WHERE id = ?")->execute([$vid]);
            }
        }

        $stmt = $pdo->prepare("SELECT v.id, v.ends_at, v.juego_id, j.fecha, j.rival FROM motm_votaciones v JOIN juegos j ON j.id = v.juego_id WHERE v.status = 'open' AND v.ends_at > ? ORDER BY v.ends_at ASC LIMIT 1");
        $stmt->execute([$now]);
        $motmOpen = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($motmOpen) {
            $stmt2 = $pdo->prepare("SELECT id, nombre, foto_url, orden FROM motm_nominees WHERE votacion_id = ? ORDER BY orden ASC");
            $stmt2->execute([$motmOpen['id']]);
            $motmOpen['nominees'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        if (!$motmOpen) {
            $stmt = $pdo->query("SELECT v.id, v.winner_nominee_id, n.nombre AS winner_nombre, n.foto_url AS winner_foto FROM motm_votaciones v LEFT JOIN motm_nominees n ON n.id = v.winner_nominee_id WHERE v.status = 'closed' AND v.winner_nominee_id IS NOT NULL ORDER BY v.ends_at DESC LIMIT 1");
            $motmWinner = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($motmWinner) {
                $st = $pdo->prepare("SELECT COUNT(*) FROM motm_votes WHERE votacion_id = ?");
                $st->execute([$motmWinner['id']]);
                $motmWinner['total_votes'] = (int) $st->fetchColumn();
                $st = $pdo->prepare("SELECT COUNT(*) FROM motm_votes WHERE votacion_id = ? AND nominee_id = ?");
                $st->execute([$motmWinner['id'], $motmWinner['winner_nominee_id']]);
                $motmWinner['winner_votes'] = (int) $st->fetchColumn();
                $motmWinner['winner_pct'] = $motmWinner['total_votes'] > 0 ? round($motmWinner['winner_votes'] / $motmWinner['total_votes'] * 100, 1) : 0;
            }
        }
    } catch (PDOException $e) {
        error_log('public-nav-context MOTM: ' . $e->getMessage());
    }
}

if (!isset($star_section_visible)) {
    $star_section_visible = false;
    try {
        $stmt = $pdo->query("SELECT id FROM jugador_mes ORDER BY created_at DESC LIMIT 1");
        $star_section_visible = (bool) $stmt->fetch();
    } catch (PDOException $e) {
        error_log('public-nav-context Star: ' . $e->getMessage());
    }
}
