<?php
/**
 * Apply sql/migrate_match_reels_stats.sql (CLI only recommended).
 */
declare(strict_types=1);

require __DIR__ . '/../config/database.php';

$sqlPath = dirname(__DIR__) . '/sql/migrate_match_reels_stats.sql';
$sql = file_get_contents($sqlPath);
if ($sql === false || trim($sql) === '') {
    fwrite(STDERR, "Missing SQL file.\n");
    exit(1);
}

foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if ($stmt === '') {
        continue;
    }
    try {
        $pdo->exec($stmt . ';');
        echo "OK: " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 72) . "...\n";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'Duplicate column') !== false) {
            echo "Skip duplicate column (already migrated).\n";
            continue;
        }
        fwrite(STDERR, $msg . "\n");
        exit(1);
    }
}

echo "match_reels stats migration finished.\n";
