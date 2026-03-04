# Resumen de datos – InfinityFree (valenciafc-academyhouston.rf.gd)

Usa este resumen para configurar la web. **No subas este archivo al servidor** si incluyes contraseñas; guárdalo solo en tu PC.

---

## Cuenta hosting

| Dato | Valor |
|------|--------|
| Usuario | `if0_41281527` |
| Contraseña | *(la del panel – Account Details)* |
| Directorio base | `/home/vol16_2/infinityfree.com/if0_41281527` |
| Dominio | `valenciafc-academyhouston.rf.gd` |
| Estado | Active |

---

## FTP (subir archivos)

| Dato | Valor |
|------|--------|
| Host | `ftpupload.net` |
| Usuario | `if0_41281527` |
| Contraseña | *(la del panel – FTP Details)* |
| Puerto | `21` |
| Carpeta web | `htdocs` (dentro de tu cuenta) |

En FileZilla: conéctate a `ftpupload.net` con ese usuario y contraseña, luego entra en la carpeta **htdocs** y sube ahí el contenido del proyecto.

---

## MySQL (base de datos)

| Dato | Valor |
|------|--------|
| Host | `sql101.infinityfree.com` |
| Usuario | `if0_41281527` |
| Contraseña | *(la del panel – MySQL Connection Details)* |
| Puerto | `3306` |
| Base de datos | `if0_41281527_valenciacf` (ya creada en tu cuenta) |

**Importante:** En phpMyAdmin abre la base de datos **`if0_41281527_valenciacf`** e importa **`sql/schema-infinityfree.sql`** si aún no lo has hecho.

---

## Pasos para dejar la web funcionando

1. **Base de datos**  
   Ya tienes **`if0_41281527_valenciacf`**. Si no has importado las tablas, sigue el paso 2.

2. **Importar tablas**  
   Panel → MySQL Databases → botón **phpMyAdmin** de la BD `if0_41281527_valenciacf` → Importar → subir **`sql/schema-infinityfree.sql`**.

3. **Subir archivos**  
   - Por **File Manager:** Panel → File Manager → abrir **htdocs** → subir el contenido de tu carpeta del proyecto (index.php, admin/, assets/, config/, includes/, sql/).  
   - Por **FTP:** FileZilla con los datos de arriba, entrar en **htdocs** y subir ese mismo contenido.

4. **Configurar la base de datos en la web**  
   En el servidor (File Manager o FTP), dentro de **config/** crea el archivo **`database.local.php`** con:

   ```php
   <?php
   $DB_HOST = 'sql101.infinityfree.com';
   $DB_NAME = 'if0_41281527_valenciacf';
   $DB_USER = 'if0_41281527';
   $DB_PASS = 'TU_CONTRASEÑA_MYSQL';     // La del panel (MySQL Password)
   ```

   Sustituye `TU_CONTRASEÑA_MYSQL` por la contraseña MySQL del panel.

5. **Probar**  
   - Web: https://valenciafc-academyhouston.rf.gd  
   - Admin: https://valenciafc-academyhouston.rf.gd/admin/  
   - Usuario: `admin` / Contraseña: `password` → **cámbiala** en Change password.

---

## Despliegue automático (desde tu PC)

Si usas el script **`deploy.php`**:

1. En **`config/deploy-credentials.php`** (cópialo de `deploy-credentials.example.php` si no existe) pon:
   - `ftp_pass` = contraseña FTP del panel  
   - `db_pass` = contraseña MySQL del panel  
   - `db_name` = `if0_41281527_valenciacf`

2. Crea antes la BD e importa **`sql/schema-infinityfree.sql`** (pasos 1 y 2 de arriba).

3. En la carpeta del proyecto ejecuta:
   ```bash
   php deploy.php
   ```

El script subirá los archivos por FTP y creará **config/database.local.php** en el servidor con los datos de MySQL.
