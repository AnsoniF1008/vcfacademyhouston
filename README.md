# VCF Academy Houston – Landing Page

Responsive landing page for the VCF Academy Houston youth soccer academy, inspired by the official Valencia CF aesthetic.

**Documentación completa del sistema (arquitectura, panel admin, APIs, BD, deploy):** [docs/MANUAL-DEL-SISTEMA.md](docs/MANUAL-DEL-SISTEMA.md) (español).

## Stack

- **PHP 8.x** with PDO (prepared statements)
- **MySQL** (utf8mb4)
- **Frontend:** HTML5, CSS3, Bootstrap 5, FontAwesome
- **Fonts:** Oswald (headings), Montserrat (body) via Google Fonts

## Clonar y configurar

Si clonas el repositorio:

1. **Clonar:** `git clone <URL_DEL_REPOSITORIO>` y entrar en la carpeta del proyecto.
2. **Base de datos local:** Copiar `config/database.infinityfree.example.php` o `config/database.hostinger.example.php` a `config/database.local.php` y rellenar host, usuario, contraseña y nombre de base de datos. La aplicación usa `database.local.php` si existe; si no, usa los valores por defecto de `config/database.php`.
3. **Deploy (opcional):** Si usas los scripts de despliegue, copiar `config/deploy-credentials.example.php` a `config/deploy-credentials.php` y completar credenciales (FTP/SSH, etc.).
4. **Base de datos:** Crear la base de datos en MySQL e importar `sql/schema.sql` (y las migraciones en `sql/` que necesites según el estado del esquema).

## Setup (XAMPP)

1. **Database**
   - Open phpMyAdmin or MySQL CLI.
   - Create a database (or use the one in the script):
     ```sql
     CREATE DATABASE if0_41281527_valenciacf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
     ```
   - Import the schema:
     ```bash
     mysql -u root -p if0_41281527_valenciacf < sql/schema.sql
     ```
     Or in phpMyAdmin: select `if0_41281527_valenciacf`, then Import → choose `sql/schema.sql`.

2. **Configuration**
   - Edit `config/database.php` if needed. Defaults:
     - Host: `localhost`
     - Database: `if0_41281527_valenciacf`
     - User: `root`
     - Password: empty

3. **Admin login**
   - URL: `http://localhost/valencia/admin/`
   - Default credentials: **admin** / **password**
   - Change the password after first login (e.g. update `admin_users` with a new `password_hash` from `password_hash('your_new_password', PASSWORD_DEFAULT)`).

4. **Images**
   - **Hero:** Place a hero image at `assets/img/hero.jpg` (e.g. kids training, Valencia-style). If missing, the hero section uses a dark gradient background.
   - **Jugador del Mes:** Photos are uploaded via the admin panel and stored in `assets/uploads/`.

5. **Existing database?** Run migrations as needed:
   - `sql/migrate_contact_join.sql` – adds `contact_messages`, `inscripciones`.
   - `sql/migrate_sedes_canchas.sql` – converts `sedes` to use `mapa_general_url` and adds table `canchas` (parks with specific fields). Run only if your sedes table still has `link_google_maps`.
   - `sql/migrate_sedes_nota_sobrenombre.sql` – adds `nota_acceso` (per-park access note) and `sobrenombre` (field nickname, e.g. "The Mestalla Pitch") to sedes/canchas. Run once.

## Project structure

- `index.php` – Main landing (Hero, Methodology, Grounds with accordions per park/field, Tournaments, Star of the Month).
- `contact.php`, `privacy.php`, `join.php` – Contact form, privacy policy, academy registration.
- `config/database.php` – PDO connection.
- `sql/schema.sql` – Tables: `admin_users`, `sedes`, `canchas`, `torneos`, `jugador_mes`, `categorias`, `contact_messages`, `inscripciones`.
- `includes/header.php`, `includes/footer.php` – Layout.
- `assets/css/style.css` – VCF palette and components.
- `admin/` – Password-protected panel: login, dashboard, CRUD for Jugador del Mes, Sedes, Torneos, Categories; change password.

## Design

- **Colors:** #1A1A1A (black), #FF6600 (orange), #FFFFFF (white).
- **UI:** Rounded cards, orange borders/shadows on hover, mobile-first responsive layout.
