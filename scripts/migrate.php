<?php
/**
 * VCF Academy Houston — unified, tracked SQL migration runner.
 *
 * Replaces the ad-hoc per-migration runner scripts with a single tool that
 * records which sql/migrate_*.sql files have been applied in a
 * `schema_migrations` table, so each migration runs exactly once. ALTER TABLE
 * ADD COLUMN statements in these files are NOT idempotent on MySQL, so "run
 * once" tracking (not re-running) is the whole point.
 *
 * Commands (CLI only):
 *   php scripts/migrate.php --status     List applied vs pending migrations.
 *   php scripts/migrate.php --baseline   Mark ALL current migrations as applied
 *                                        WITHOUT executing them. Use once on an
 *                                        existing/production DB that already has
 *                                        the schema, so only NEW migrations run
 *                                        from here on.
 *   php scripts/migrate.php --pretend    Show what would run, change nothing.
 *   php scripts/migrate.php              Apply all pending migrations in order.
 *
 * New migrations: name them so they sort in apply order, e.g.
 *   sql/migrate_2026_06_10_add_foo.sql
 *
 * NOTE: the runtime SHOW COLUMNS guards in admin/* and api/* are intentionally
 * kept as a safety net until this runner is part of the deploy process. See
 * docs/MIGRATIONS.md.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require __DIR__ . '/migrate_helpers.php';

// ── Parse args ──────────────────────────────────────────────────────────────
$mode = 'apply';
foreach (array_slice($argv, 1) as $arg) {
    switch ($arg) {
        case '--status':   $mode = 'status'; break;
        case '--baseline': $mode = 'baseline'; break;
        case '--pretend':  $mode = 'pretend'; break;
        case '--help': case '-h':
            echo "Usage: php scripts/migrate.php [--status|--baseline|--pretend]\n";
            exit(0);
        default:
            fwrite(STDERR, "Unknown option: $arg\n");
            exit(2);
    }
}

require __DIR__ . '/../config/database.php';
/** @var PDO $pdo */

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS schema_migrations (
        filename VARCHAR(255) NOT NULL PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$applied = [];
foreach ($pdo->query("SELECT filename FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN) as $f) {
    $applied[$f] = true;
}

$files = glob(__DIR__ . '/../sql/migrate_*.sql') ?: [];
sort($files);
$record = $pdo->prepare("INSERT INTO schema_migrations (filename) VALUES (?)");

$pending = [];
foreach ($files as $path) {
    $name = basename($path);
    if (!isset($applied[$name])) {
        $pending[] = $path;
    }
}

if ($mode === 'status') {
    echo "Applied (" . count($applied) . "):\n";
    foreach (array_keys($applied) as $f) { echo "  [x] $f\n"; }
    echo "\nPending (" . count($pending) . "):\n";
    foreach ($pending as $p) { echo "  [ ] " . basename($p) . "\n"; }
    exit(0);
}

if ($mode === 'baseline') {
    $n = 0;
    foreach ($pending as $path) {
        $record->execute([basename($path)]);
        echo "  baselined: " . basename($path) . "\n";
        $n++;
    }
    echo "Baseline complete: $n migration(s) recorded as applied (not executed).\n";
    exit(0);
}

if (empty($pending)) {
    echo "Nothing to migrate. All " . count($applied) . " migration(s) already applied.\n";
    exit(0);
}

$failed = 0;
foreach ($pending as $path) {
    $name = basename($path);
    $sql = (string) file_get_contents($path);
    $statements = vcf_split_sql($sql);

    if ($mode === 'pretend') {
        echo "WOULD APPLY $name (" . count($statements) . " statement(s))\n";
        continue;
    }

    echo "Applying $name (" . count($statements) . " statement(s))...\n";
    $fileOk = true;
    foreach ($statements as $stmt) {
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            if (vcf_migration_error_is_benign($e)) {
                echo "    ~ tolerated: " . trim($e->getMessage()) . "\n";
                continue;
            }
            fwrite(STDERR, "    ! FAILED: " . $e->getMessage() . "\n");
            fwrite(STDERR, "      in statement: " . substr(preg_replace('/\s+/', ' ', $stmt), 0, 120) . "...\n");
            $fileOk = false;
            break;
        }
    }
    if ($fileOk) {
        $record->execute([$name]);
        echo "  [OK] $name recorded.\n";
    } else {
        $failed++;
        fwrite(STDERR, "  [STOP] $name not recorded; fix it and re-run.\n");
        break; // Don't apply later migrations on top of a failed one.
    }
}

echo $failed > 0 ? "Migration halted with errors.\n" : "Migration complete.\n";
exit($failed > 0 ? 1 : 0);
