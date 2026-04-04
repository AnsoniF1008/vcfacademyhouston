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

## Deploy automático (GitHub Actions)

El workflow `.github/workflows/deploy.yml` sube en cada push a `main` usando **secretos del repositorio**:

| Secreto | Uso |
|---------|-----|
| `FTP_SERVER` | Host FTP (p. ej. `ftp.vcfacademyhouston.com` o la IP del panel) |
| `FTP_USERNAME` | Usuario FTP (exactamente como en Hostinger, mayúsculas/minúsculas) |
| `FTP_PASSWORD` | Contraseña FTP |

Si el workflow falla con error de login o conexión, revisa en GitHub: **Settings → Secrets and variables → Actions** que existan los tres y coincidan con el panel de Hostinger. El script local (`deploy-hostinger-ftp.ps1`) no usa esos secretos; solo usa `config/deploy-credentials.php`.

## Ruta remota (`hostinger_ftp_remote_path`)

En Hostinger, muchas cuentas FTP **ya abren en `public_html`** (es lo que ves en el administrador como `.../files/public_html/`).

- **`''` (recomendado en ese caso):** los archivos van a `index.php` en la raíz FTP = la web pública. Si pusieras `'public_html'` aquí, se crearía **`public_html/public_html/`** y el sitio no se actualizaría.
- **`'public_html'`:** solo si al conectar por FTP la raíz es la **home** de la cuenta y ves `public_html` como **subcarpeta** (junto a `logs`, etc.).

El workflow de GitHub usa `server-dir: /` por la misma razón (raíz FTP = sitio).

Si tras un deploy el sitio no cambia, revisa en File Manager dónde quedó `index.php` y ajusta esta clave.
