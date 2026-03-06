# Subir a producción (Hostinger)

**Producción:** https://vcfacademyhouston.com

## Cómo desplegar

Desde la raíz del proyecto, en PowerShell:

```powershell
.\scripts\deploy-hostinger-ftp.ps1
```

No hace falta pasar contraseña: el script lee la conexión FTP de **config/deploy-credentials.php** (`hostinger_ftp_pass`).

## Conexión FTP guardada

| Dato | Valor |
|------|--------|
| **Host** | ftp.vcfacademyhouston.com |
| **Usuario** | u766140586.ansonif |
| **Contraseña** | En `config/deploy-credentials.php` → `hostinger_ftp_pass` |
| **Directorio remoto** | public_html (raíz del sitio) |

Si cambias la contraseña FTP en el panel de Hostinger, actualiza `hostinger_ftp_pass` en `config/deploy-credentials.php`.
