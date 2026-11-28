# Agents

## Cursor Cloud specific instructions

### Overview
VCF Academy Houston is a vanilla PHP 8.x + MySQL landing page and admin panel for a youth soccer academy. No framework, no Composer, no npm — all vendor libraries are statically bundled in `assets/vendor/`.

### Services

| Service | How to start | Notes |
|---------|-------------|-------|
| **MySQL** | `sudo service mysql start` | Must be running before PHP server. Root user with empty password over TCP (`127.0.0.1`). |
| **PHP dev server** | `php -S 0.0.0.0:8000` (from repo root) | Public site at `/index.php`, Admin at `/admin/` |

### Database
- DB name: `if0_41281527_valenciacf`
- Schema: `sql/schema.sql` (run with `sudo mysql < sql/schema.sql`)
- Migrations: `sql/migrate_*.sql` files (safe to run repeatedly; duplicates produce harmless errors)
- Default admin login: `admin` / `password`

### Key gotcha: MySQL socket vs TCP
The default `config/database.php` uses `localhost` which maps to a Unix socket. In this environment, the socket has permission issues. A `config/database.local.php` (gitignored) is used to override with `127.0.0.1` (TCP). If DB connections fail, ensure `config/database.local.php` exists with `$DB_HOST = '127.0.0.1'`.

### Linting
No dedicated linter tool is configured. Use `php -l <file>` for syntax checking individual PHP files.

### Testing
No automated test framework is set up. Manual testing via browser: public site at `http://localhost:8000`, admin at `http://localhost:8000/admin/`.

### Deployment
See `README.md` and `docs/DEPLOY-PRODUCCION.md`. Production deploy uses FTP via `scripts/deploy-hostinger-ftp.ps1`.
