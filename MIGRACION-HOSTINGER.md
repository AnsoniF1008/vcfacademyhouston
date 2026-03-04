# Migración VCF Academy Houston a Hostinger

Sigue estos pasos para subir el sitio y la base de datos a tu nuevo hosting.

---

## 1. Preparar los archivos en tu PC

En la carpeta del proyecto (XAMPP/valencia), abre terminal y ejecuta:

```bash
php scripts/build_export_for_hostinger.php
```

Se creará **valencia-website.zip** en la misma carpeta. Ese ZIP contiene todo el sitio **sin** credenciales locales (listo para subir).

### Base de datos (SQL)

**Opción A – Desde la terminal (si tienes mysqldump en el PATH o XAMPP):**

```bash
php scripts/export_database.php
```

Se generará **export/valencia-database.sql**.

**Opción B – Desde phpMyAdmin (recomendado si lo anterior falla):**

1. Abre phpMyAdmin (http://localhost/phpmyadmin).
2. Selecciona la base de datos del proyecto.
3. Pestaña **Exportar** → método **Rápido**, formato **SQL**.
4. Descarga el archivo y guárdalo como **valencia-database.sql**.

---

## 2. En el panel de Hostinger

### Crear la base de datos

1. Entra a **Bases de datos MySQL** (o **MySQL Databases**).
2. Crea una base de datos nueva y un usuario MySQL con contraseña.
3. Anota: **host** (ej. localhost), **nombre de la base de datos**, **usuario** y **contraseña**.

### Subir archivos en el asistente de migración

1. En el paso donde pide **“Subir los archivos de backup”**:
   - Sube **valencia-website.zip** (archivos del sitio).
   - Sube **valencia-database.sql** (base de datos).
2. Si Hostinger pide un solo archivo primero, sube el ZIP; luego en el paso de “importar base de datos” o “subir SQL” usa el archivo **valencia-database.sql**.
3. Completa el asistente para que descomprima el ZIP en la carpeta pública (public_html o la que te indique).

### Importar la base de datos

1. En Hostinger, abre **phpMyAdmin** (o la herramienta de bases de datos que te den).
2. Selecciona la base de datos que creaste.
3. **Importar** → elige **valencia-database.sql** → Ejecutar.

---

## 3. Configurar la conexión a la BD en el servidor

En el servidor, en la carpeta del sitio:

1. Copia **config/database.hostinger.example.php** y renómbralo a **config/database.local.php**.
2. Edita **config/database.local.php** y pon los datos de tu base de datos de Hostinger:

```php
$DB_HOST = 'localhost';              // El host que te dé Hostinger
$DB_NAME = 'u123456789_valencia';     // Nombre de tu BD
$DB_USER = 'u123456789_admin';       // Usuario MySQL
$DB_PASS = 'TuContraseñaSegura';      // Contraseña del usuario
```

3. Guarda el archivo. La web usará automáticamente esta configuración en el servidor.

---

## 4. Comprobar que todo funciona

1. Abre tu dominio (o la URL temporal de Hostinger).
2. Comprueba la página principal, menú y enlaces.
3. Entra al **admin** (ej. tudominio.com/valencia/admin o la ruta que tengas) e inicia sesión.
4. Revisa que se vean jugadores, partidos, votación MOTM, etc.

Si ves “Database connection failed”, revisa **config/database.local.php** (host, nombre de BD, usuario y contraseña).

---

## Resumen de archivos que subes a Hostinger

| Archivo              | Dónde se genera              | Para qué |
|----------------------|-----------------------------|----------|
| valencia-website.zip| Raíz del proyecto (script)  | Código y recursos del sitio |
| valencia-database.sql| export/ o phpMyAdmin        | Base de datos MySQL |

Después de subir el ZIP e importar el SQL, solo falta crear **config/database.local.php** en el servidor con los datos de la BD de Hostinger.
