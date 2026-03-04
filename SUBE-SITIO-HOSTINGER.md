# Subir el sitio a Hostinger en 3 pasos

## Opción 1: Conectar por Cursor y arrastrar (la más fácil)

1. **Conectar por SSH desde Cursor**
   - Pulsa **Ctrl+Shift+P**
   - Escribe **Remote-SSH: Connect to Host**
   - Elige **hostinger-vcf**
   - Cuando pida contraseña, escribe la de SSH (la del panel de Hostinger → Acceso SSH)

2. **Abrir la carpeta del sitio en el servidor**
   - Cuando Cursor se conecte, te pedirá abrir una carpeta
   - Elige: **domains** → **vcfacademyhouston.com** → **public_html**  
     (o la carpeta que Hostinger te muestre como “raíz del sitio”)

3. **Subir los archivos**
   - En el explorador de Cursor (panel izquierdo), en **REMOTE** verás la carpeta del servidor
   - En tu PC, abre la carpeta del proyecto: `c:\xampp\htdocs\valencia`
   - Arrastra **todo el contenido** (admin, assets, config, includes, api, sql, index.php, join.php, contact.php, calendar.php, privacy.php, deploy.php, scripts) dentro de **public_html** en el panel REMOTE  
   - No arrastres la carpeta **.git** ni **config/database.local.php** (no la tienes en el proyecto; la crearás en el servidor)

4. **Después de subir**
   - En el panel de Hostinger crea la base de datos MySQL e importa tu **valencia-database.sql** (exportado desde phpMyAdmin local)
   - En el servidor (desde Cursor, ya conectado) crea el archivo **config/database.local.php** con el contenido de **config/database.hostinger.example.php** y rellena host, nombre de BD, usuario y contraseña que te dé Hostinger

---

## Opción 2: Script automático (cuando Posh-SSH esté instalado)

En PowerShell, desde la carpeta del proyecto:

```powershell
.\scripts\deploy-hostinger-ssh.ps1 -Password 'TU_CONTRASEÑA_SSH'
```

La primera vez puede instalar el módulo Posh-SSH (acepta si pregunta). Luego sube los archivos por SSH.

---

## Opción 3: ZIP por el panel de Hostinger

1. En tu PC ya tienes **valencia-website.zip** (generado con `php scripts/build_export_for_hostinger.php`)
2. En Hostinger → **Administrador de archivos** → entra en **public_html**
3. Sube **valencia-website.zip** y descomprímelo ahí (clic derecho → Extraer)
4. Crea la base de datos, importa el .sql y crea **config/database.local.php** como en el paso 4 de la Opción 1
