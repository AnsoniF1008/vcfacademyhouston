<?php
/**
 * Pure helpers for scripts/migrate.php (no DB / side effects, so they can be
 * unit-tested in isolation).
 */
declare(strict_types=1);

if (!function_exists('vcf_split_sql')) {
    /**
     * Split a SQL script into individual statements, respecting string literals
     * (', ", `), backslash escapes, doubled-quote escapes, and line (-- , #) and
     * block comments. Returns trimmed, non-empty statements.
     *
     * @return string[]
     */
    function vcf_split_sql(string $sql): array
    {
        $stmts = [];
        $buf = '';
        $len = strlen($sql);
        $i = 0;
        $quote = null;        // current quote char (' " `) or null
        $lineComment = false; // inside -- or # comment
        $blockComment = false;

        while ($i < $len) {
            $ch = $sql[$i];
            $next = $i + 1 < $len ? $sql[$i + 1] : '';

            if ($lineComment) {
                if ($ch === "\n") {
                    $lineComment = false;
                    $buf .= $ch;
                }
                $i++;
                continue;
            }
            if ($blockComment) {
                if ($ch === '*' && $next === '/') {
                    $blockComment = false;
                    $i += 2;
                    continue;
                }
                $i++;
                continue;
            }
            if ($quote !== null) {
                $buf .= $ch;
                if ($ch === '\\') {
                    if ($next !== '') {
                        $buf .= $next;
                        $i += 2;
                        continue;
                    }
                } elseif ($ch === $quote) {
                    if ($next === $quote) {
                        $buf .= $next;
                        $i += 2;
                        continue;
                    }
                    $quote = null;
                }
                $i++;
                continue;
            }

            if ($ch === '-' && $next === '-') { $lineComment = true; $i += 2; continue; }
            if ($ch === '#') { $lineComment = true; $i++; continue; }
            if ($ch === '/' && $next === '*') { $blockComment = true; $i += 2; continue; }
            if ($ch === "'" || $ch === '"' || $ch === '`') { $quote = $ch; $buf .= $ch; $i++; continue; }

            if ($ch === ';') {
                $trimmed = trim($buf);
                if ($trimmed !== '') {
                    $stmts[] = $trimmed;
                }
                $buf = '';
                $i++;
                continue;
            }

            $buf .= $ch;
            $i++;
        }

        $trimmed = trim($buf);
        if ($trimmed !== '') {
            $stmts[] = $trimmed;
        }
        return $stmts;
    }
}

if (!function_exists('vcf_migration_error_is_benign')) {
    /** Error codes meaning "already applied" — safe to tolerate on re-run. */
    function vcf_migration_error_is_benign(PDOException $e): bool
    {
        $code = isset($e->errorInfo[1]) ? (int) $e->errorInfo[1] : 0;
        // 1050 table exists, 1060 dup column, 1061 dup key, 1062 dup entry,
        // 1091 can't DROP (missing).
        return in_array($code, [1050, 1060, 1061, 1062, 1091], true);
    }
}
