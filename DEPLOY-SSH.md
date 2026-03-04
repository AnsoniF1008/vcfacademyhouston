# Despliegue con Remote SSH

Cuando te conectes al servidor con la extensión **Remote SSH**, sigue estos pasos para subir y configurar la web.

---

## 1. Conectar

- En Cursor/VS Code: **Remote-SSH: Connect to Host** y elige tu host (o escribe usuario@tu-servidor).
- Abre una terminal en el servidor (Terminal → New Terminal).

---

## 2. Ubicación de la web en el servidor

En el servidor, la carpeta pública suele ser:

- **Apache (típico):** `/var/www/html` o `/var/www/tudominio`
- **InfinityFree** (si dieran SSH): suelen usar **FTP**; con SSH sería la ruta que te indiquen.

Navega a esa carpeta, por ejemplo:

```bash
cd /var/www/html
```

(Sustituye por la ruta que uses en tu hosting.)

---

## 3. Subir los archivos del proyecto

**Opción A – Desde tu PC (con Remote SSH):**  
Con la conexión SSH abierta, arrastra la carpeta del proyecto al explorador remoto de Cursor/VS Code y suelta dentro de la carpeta pública (p. ej. `html`). Luego en el servidor mueve o copia solo el **contenido** (los `.php`, `admin/`, `assets/`, etc.) para que queden directamente en la raíz de la web, no dentro de una subcarpeta “valencia”.

**Opción B – Desde terminal en el servidor:**  
Si tienes `git` en el servidor y el proyecto está en un repositorio:

```bash
cd /var/www/html
git clone URL_DE_TU_REPO .
```

(O clona en una carpeta y luego copia el contenido a la raíz.)

**Opción C – Desde tu PC con SCP:**  
En una terminal **local** (no en la sesión SSH):

```bash
scp -r c:\xampp\htdocs\valencia\* usuario@tu-servidor:/var/www/html/
```

(Ajusta rutas y usuario@servidor.)

---

## 4. Base de datos en el servidor

- Crea la base de datos y el usuario MySQL (panel del hosting o por SSH si tienes `mysql`).
- Importa las tablas. En el servidor, desde la carpeta del proyecto:

```bash
mysql -u TU_USUARIO -p TU_BASE_DE_DATOS < sql/schema-infinityfree.sql
```

(O usa phpMyAdmin si lo tienes.)

---

## 5. Configurar la conexión a la BD

**Opción rápida (script):** Si subiste la carpeta `scripts/`, desde la raíz de la web en el servidor:

1. Edita el script y sustituye los placeholders por tus datos reales (host, nombre BD, usuario, contraseña MySQL), o bien ejecuta el script y luego edita el archivo generado:

```bash
bash scripts/create-database-local-on-server.sh
```

2. El script crea `config/database.local.php` con un **placeholder** en `$DB_PASS` (`TU_CONTRASEÑA_MYSQL`). **Debes editar ese archivo en el servidor** e insertar tu contraseña MySQL real:

```bash
nano config/database.local.php
```

Sustituye `TU_CONTRASEÑA_MYSQL` por la contraseña que te da el panel del hosting. Guarda (Ctrl+O, Enter, Ctrl+X).

Si usas otro hosting (no InfinityFree), edita el script antes de ejecutarlo para poner el host, nombre de BD y usuario correctos, o usa la opción manual abajo.

**Opción manual:** En el servidor, crea o edita el archivo de configuración local:

```bash
nano config/database.local.php
```

Contenido (ajusta con los datos reales del servidor):

```php
<?php
$DB_HOST = 'localhost';   // o la IP/host que use tu MySQL
$DB_NAME = 'nombre_de_tu_bd';
$DB_USER = 'tu_usuario_mysql';
$DB_PASS = 'tu_contraseña_mysql';
```

Guarda (en nano: Ctrl+O, Enter, Ctrl+X).

---

## 6. Permisos

La carpeta de subidas debe ser escribible por el servidor web:

```bash
chmod -R 755 assets/uploads
```

(Algunos hostings usan otro usuario; si fallan las subidas de fotos, el soporte te dirá el usuario y podrás usar `chown`.)

---

## 7. Comprobar

- Abre en el navegador la URL de tu dominio.
- Entra a `/admin/` (usuario `admin`, contraseña `password`) y cambia la contraseña.

---

**Resumen:** Con Remote SSH te conectas al servidor, subes el contenido del proyecto a la raíz web, importas el schema en MySQL, creas `config/database.local.php` con los datos del servidor y ajustas permisos de `assets/uploads`.
