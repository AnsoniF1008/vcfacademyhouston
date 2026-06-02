# Database migrations

A single tracked runner — `scripts/migrate.php` — applies the
`sql/migrate_*.sql` files exactly once each, recording applied files in a
`schema_migrations` table. This replaces the older one-off
`scripts/run_migrate_*.php` scripts (left in place but no longer the way to
migrate).

Why tracking matters: `CREATE TABLE` statements use `IF NOT EXISTS`, but the
`ALTER TABLE ... ADD COLUMN` statements do **not** (MySQL has no portable
`ADD COLUMN IF NOT EXISTS`), so re-running a migration errors with "duplicate
column". Recording applied files means each runs once.

## First-time setup on the EXISTING production DB ⚠️

The live database already has the full schema (migrations were applied by hand
previously). Do **not** re-run them. Instead, baseline once so the runner knows
they're already applied:

```bash
php scripts/migrate.php --baseline   # records all current migrations, runs none
php scripts/migrate.php --status     # verify: everything Applied, nothing Pending
```

On a brand-new/empty database, skip `--baseline` and just run
`php scripts/migrate.php` (after loading `sql/schema.sql` if you use it as the
base).

## Day-to-day

```bash
php scripts/migrate.php --status     # what's applied vs pending
php scripts/migrate.php --pretend    # dry run: list statements, change nothing
php scripts/migrate.php              # apply all pending, in filename order
```

The runner tolerates "already exists" errors (codes 1050/1060/1061/1062/1091)
so a partially-applied migration can be re-run, and it stops at the first hard
failure without recording it (fix and re-run).

## Adding a new migration

Create `sql/migrate_YYYY_MM_DD_short_description.sql`. The date prefix makes new
migrations sort after the legacy un-prefixed ones, so apply order is correct.
Prefer `CREATE TABLE IF NOT EXISTS`; for column adds, accept that they run once.

## Runtime schema guards (kept on purpose)

`admin/*` and `api/*` still contain `SHOW COLUMNS ...` guards that degrade
gracefully when a migration hasn't been applied (e.g. `users.php` shows
"run the RBAC migration first"). These are intentionally **kept** as a safety
net. Retire them only once `scripts/migrate.php` is a guaranteed part of the
deploy process (e.g. invoked from `deploy.php` or a deploy hook) — otherwise a
forgotten migration would turn a friendly message into a fatal error. Retiring
them is a separate, deploy-process change, not part of introducing this runner.
