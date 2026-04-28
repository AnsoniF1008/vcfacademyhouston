<?php
/**
 * Closes expired MOTM votings in a single batch.
 * Called only from admin pages (dashboard / motm.php) and from CLI cron, never from the public front.
 *
 * Public pages just filter by `ends_at > NOW()` so an expired-but-not-yet-closed voting simply does not show.
 */
declare(strict_types=1);

if (!function_exists('vcf_motm_close_expired')) {
    function vcf_motm_close_expired(PDO $pdo): int
    {
        $closed = 0;
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("SELECT id FROM motm_votaciones WHERE status = 'open' AND ends_at <= ?");
            $stmt->execute([$now]);
            $expired = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!$expired) {
                return 0;
            }

            $ph = implode(',', array_fill(0, count($expired), '?'));
            $stmt = $pdo->prepare(
                "SELECT votacion_id, nominee_id, COUNT(*) AS cnt
                 FROM motm_votes
                 WHERE votacion_id IN ($ph)
                 GROUP BY votacion_id, nominee_id"
            );
            $stmt->execute($expired);

            $winners = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $vid = (int) $row['votacion_id'];
                $nid = (int) $row['nominee_id'];
                $cnt = (int) $row['cnt'];
                if (!isset($winners[$vid])
                    || $cnt > $winners[$vid]['cnt']
                    || ($cnt === $winners[$vid]['cnt'] && $nid < $winners[$vid]['nominee_id'])
                ) {
                    $winners[$vid] = ['nominee_id' => $nid, 'cnt' => $cnt];
                }
            }

            $updWithWinner = $pdo->prepare("UPDATE motm_votaciones SET status = 'closed', winner_nominee_id = ? WHERE id = ?");
            $updNoWinner = $pdo->prepare("UPDATE motm_votaciones SET status = 'closed' WHERE id = ?");
            foreach ($expired as $vid) {
                $vid = (int) $vid;
                if (isset($winners[$vid])) {
                    $updWithWinner->execute([$winners[$vid]['nominee_id'], $vid]);
                } else {
                    $updNoWinner->execute([$vid]);
                }
                $closed++;
            }
        } catch (PDOException $e) {
            error_log('vcf_motm_close_expired: ' . $e->getMessage());
        }
        return $closed;
    }
}
