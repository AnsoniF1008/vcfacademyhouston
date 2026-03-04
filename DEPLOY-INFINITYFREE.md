# Subir la web a InfinityFree (valenciafc-academyhouston.rf.gd)

Sigue estos pasos para publicar tu sitio en InfinityFree.

---

## Despliegue automático desde tu PC (recomendado)

1. Crea la base de datos **valencia** en el panel (MySQL Databases → Create Database).
2. En phpMyAdmin, selecciona esa BD e importa **`sql/schema-infinityfree.sql`**.
3. Copia **`config/deploy-credentials.example.php`** a **`config/deploy-credentials.php`** y rellena:
   - `ftp_pass`: tu contraseña FTP (panel → FTP Details)
   - `db_pass`: tu contraseña MySQL (panel → MySQL)
   - `db_name`: el nombre completo de la BD (ej. `if0_41281527_valencia`).
4. En la carpeta del proyecto ejecuta:
   ```bash
   php deploy.php
   ```
   El script subirá todos los archivos por FTP y creará `config/database.local.php` en el servidor con los datos de MySQL.

---

---

## 1. Crear la base de datos MySQL en InfinityFree

1. En el panel de InfinityFree, entra en **MySQL Databases** (en el menú izquierdo).
2. Crea una base de datos nueva (anota el **nombre** que te asignen, tipo `if0_41281527_nombredb`).
3. Crea un **usuario** MySQL y asígnale una contraseña (guarda usuario y contraseña).
4. Asocia ese usuario a la base de datos con **todos los privilegios**.
5. Anota:
   - **Servidor MySQL** (ej. `sql123.infinityfree.com` o el que muestre el panel)
   - **Nombre de la base de datos**
   - **Usuario**
   - **Contraseña**

Para importar las tablas:

1. Abre **phpMyAdmin** desde el enlace que te da InfinityFree (desde MySQL Databases).
2. **Selecciona tu base de datos** (ej. `if0_41281527_valencia`) en el panel izquierdo.
3. Ve a la pestaña **Importar** y sube el archivo **`sql/schema-infinityfree.sql`** (este archivo no usa CREATE DATABASE; es el adecuado para InfinityFree).
4. Pulsa **Continuar** y espera a que termine la importación.

---

## 2. Subir los archivos de la web

Tienes dos opciones.

### Opción A: File Manager (desde el panel)

1. En InfinityFree, entra en **File Manager**.
2. Navega a la carpeta **htdocs** (es la raíz de tu dominio).
3. Sube **todo el contenido** de tu carpeta del proyecto (no la carpeta “valencia”, sino lo que hay dentro):
   - `index.php`, `contact.php`, `privacy.php`, `join.php`
   - carpeta `admin/` (con todo su contenido)
   - carpeta `assets/` (css, js, img, video, uploads)
   - carpeta `config/`
   - carpeta `includes/`
   - carpeta `sql/` (opcional, solo por si quieres tener los scripts a mano)

En **htdocs** debe quedar, por ejemplo:

- `htdocs/index.php`
- `htdocs/admin/`
- `htdocs/assets/`
- `htdocs/config/`
- `htdocs/includes/`

### Opción B: FTP (FileZilla u otro cliente)

1. En el panel de InfinityFree, entra en **FTP Details** (menú izquierdo). Usa los datos que muestra:
   - **Host:** `ftpupload.net`
   - **Usuario:** tu usuario (ej. `if0_41281527`)
   - **Contraseña:** la que indica el panel en "FTP Password"
   - **Puerto:** `21`

2. Instala **FileZilla** (https://filezilla-project.org) u otro cliente FTP.

3. En FileZilla, arriba pon:
   - **Host:** el que te dio el panel
   - **Usuario:** tu usuario FTP
   - **Contraseña:** tu contraseña FTP
   - **Puerto:** 21 (si no te dicen otro)
   Pulsa **Conexión rápida**.

4. En el panel **remoto** (lado derecho), entra en la carpeta **`htdocs`**. Ahí debe subir todo.

5. En el panel **local** (izquierda), ve a la carpeta de tu proyecto (donde están `index.php`, `admin`, `assets`, etc.).

6. Arrastra **todo el contenido** de esa carpeta (archivos y carpetas) dentro de **htdocs** en el servidor. No subas la carpeta “valencia” como tal: dentro de htdocs deben quedar directamente `index.php`, `admin/`, `assets/`, `config/`, `includes/`, etc.

---

## 3. Configurar la base de datos en el servidor

Tienes dos opciones.

### Opción A: Archivo `database.local.php` (recomendado)

1. En **File Manager**, dentro de `config/`, crea un archivo nuevo llamado **`database.local.php`**.
2. Pega este contenido (usa **tu contraseña MySQL** del panel donde pone `TU_CONTRASEÑA`):

```php
<?php
$DB_HOST = 'sql101.infinityfree.com';
$DB_NAME = 'if0_41281527_valenciacf';   // El nombre exacto de tu BD en el panel
$DB_USER = 'if0_41281527';
$DB_PASS = 'TU_CONTRASEÑA';           // La que aparece en "MySQL Password" en el panel
```

3. Guarda. El sitio usará estos datos y no tendrás que tocar `database.php`.

### Opción B: Editar `database.php`

1. Abre `config/database.php` en el File Manager.
2. Sustituye las líneas de `$DB_HOST`, `$DB_NAME`, `$DB_USER` y `$DB_PASS` por los datos de InfinityFree (servidor, nombre de la BD, usuario y contraseña del panel).

---

## 4. Permisos de la carpeta de subidas

Para que se puedan subir fotos del Jugador del Mes:

1. En File Manager, ve a `assets/uploads/`.
2. Asegúrate de que la carpeta exista (si no, créala).
3. Asigna permisos **755** (o **775** si el panel lo permite) a la carpeta `uploads`.

---

## 5. Probar la web

1. Abre en el navegador: **https://valenciafc-academyhouston.rf.gd**
2. Comprueba que cargue la página principal.
3. Entra al panel de admin: **https://valenciafc-academyhouston.rf.gd/admin/**
   - Usuario: `admin`
   - Contraseña: `password`
4. **Cambia la contraseña** en cuanto entres (Admin → Change password).

---

## 6. Si el dominio no abre aún

El aviso de InfinityFree indica que los dominios nuevos pueden tardar **hasta 72 horas** en verse en todo el mundo por la propagación DNS. Si acabas de crear el dominio o de apuntarlo, espera unas horas y vuelve a intentar. No hace falta subir los archivos otra vez.

---

## Resumen rápido

| Paso | Dónde | Qué hacer |
|------|--------|-----------|
| 1 | MySQL Databases | Crear BD, usuario, anotar servidor/nombre/usuario/contraseña |
| 2 | phpMyAdmin | Importar `sql/schema-infinityfree.sql` (y migraciones si aplica) |
| 3 | File Manager o FTP | Subir todo el contenido del proyecto a **htdocs** |
| 4 | `config/database.php` | Poner los datos de MySQL de InfinityFree |
| 5 | `assets/uploads/` | Permisos 755 (o 775) |
| 6 | Navegador | Probar la web y el admin y cambiar contraseña |

Cuando sigas estos pasos, tu web estará funcionando en **valenciafc-academyhouston.rf.gd**.
