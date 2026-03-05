# Backup de producción (Hostinger) e importación a local

## 1. Hacer backup en Hostinger

Desde PowerShell, en la carpeta del proyecto:

```powershell
.\scripts\backup_from_hostinger.ps1 -Password 'TU_CONTRASEÑA_SSH'
```

(Usa la contraseña de **Acceso SSH** del panel de Hostinger.)

Se crea `backup/production-YYYYMMDD-HHMM/` con:

- `files/`: copia de todo el sitio (public_html) descargado por SCP.
- `valencia-database.sql`: exportación de la base de datos (generada en el servidor con mysqldump).

Si el script no puede ejecutar `mysqldump` en el servidor, exporta la BD manualmente desde **Hostinger → phpMyAdmin → Exportar** y guarda el archivo como `backup/production-YYYYMMDD-HHMM/valencia-database.sql`.

## 2. Importar el backup en local (XAMPP)

```bash
php scripts/import_to_local.php
```

O con una carpeta concreta:

```bash
php scripts/import_to_local.php backup/production-20250305-143000
```

El script:

- Importa `valencia-database.sql` en MySQL local (base de datos `if0_41281527_valenciacf`, usuario `root`, sin contraseña).
- Copia `files/assets/uploads/` al proyecto para tener las mismas imágenes que en producción.

Luego abre http://localhost/valencia y comprueba que todo se ve bien. No copies `config/database.local.php` del backup a tu proyecto; en local la app usa `localhost` por defecto.
