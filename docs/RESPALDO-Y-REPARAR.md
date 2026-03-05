# Respaldo y cómo reparar el sitio

## Tienes respaldo

- **Git:** Todo el proyecto está en Git. El último commit guardado es tu respaldo.
- Para volver al estado del último commit (descartar cambios locales):
  ```powershell
  cd c:\xampp\htdocs\valencia
  git restore .
  ```
- Para ver el estado actual: `git status`

---

## Reparar y dejar el sitio en línea (3 pasos)

### 1. Subir los archivos por FTP

En PowerShell, desde la carpeta del proyecto:

```powershell
cd c:\xampp\htdocs\valencia
.\scripts\deploy-hostinger-ftp.ps1 -Password 'TU_CONTRASEÑA_FTP'
```

El script sube los archivos a **las dos carpetas** que Hostinger puede usar (`public_html` y `domains/vcfacademyhouston.com/public_html`), así el sitio debería cargar en una de las dos.

### 2. Crear la base de datos en el servidor

En el **panel de Hostinger**:

1. Ve a **Bases de datos MySQL**.
2. Crea una base de datos y un usuario con contraseña.
3. Asigna ese usuario a la base de datos con todos los privilegios.
4. En **phpMyAdmin**, importa tu copia de la base de datos (por ejemplo `export/valencia-database.sql` o el .sql que tengas).

### 3. Configurar la conexión en el servidor

Por **FTP** o por el **Administrador de archivos** de Hostinger:

1. Entra en la carpeta del sitio (donde esté `index.php`) y luego en **config**.
2. Si no existe **database.local.php**, créalo (o sube una copia de `config/database.hostinger.example.php` y renómbrala a `database.local.php`).
3. Edita **database.local.php** y pon los datos que te dio Hostinger:
   - `$DB_HOST` (ej. localhost)
   - `$DB_NAME` (nombre de la base de datos)
   - `$DB_USER` (usuario MySQL)
   - `$DB_PASS` (contraseña del usuario)

Guarda el archivo. Sin este archivo la página dará error de base de datos.

---

## Comprobar

- Página principal: **https://vcfacademyhouston.com**
- Panel de admin: **https://vcfacademyhouston.com/admin/**

---

## Si sigue saliendo 404

1. **Vuelve a ejecutar el deploy** (el script ahora sube a 3 sitios: raíz FTP, `public_html` y `domains/vcfacademyhouston.com/public_html`):
   ```powershell
   .\scripts\deploy-hostinger-ftp.ps1 -Password 'TU_CONTRASEÑA_FTP'
   ```

2. **En el panel de Hostinger** → **Administrador de archivos** (o File Manager):
   - Entra en tu cuenta y navega hasta ver carpetas como `public_html` o `domains`.
   - Comprueba **dónde está** el archivo `index.php` (en qué ruta exacta).
   - En **Dominios** o **Sitios web** → vcfacademyhouston.com, mira qué pone en **Raíz del documento** / **Document root**.

3. **Dime**:
   - La ruta donde ves `index.php` en el administrador de archivos.
   - O una captura de la estructura de carpetas (donde está `index.php`).
   Con eso se puede dejar el script apuntando solo a esa carpeta.
