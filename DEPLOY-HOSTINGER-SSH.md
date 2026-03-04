# Conectar y desplegar por SSH a Hostinger (vcfacademyhouston.com)

Datos de tu plan para conexión SSH.

---

## 1. Datos de conexión SSH

| Dato        | Valor              |
|------------|---------------------|
| **Host / IP** | `31.170.166.193` |
| **Puerto**    | `65002`          |
| **Usuario**   | `u766140586`     |
| **Contraseña**| La que configuraste en Hostinger → Acceso SSH → Cambiar |

Comando para conectarte desde una terminal:

```bash
ssh -p 65002 u766140586@31.170.166.193
```

Te pedirá la contraseña SSH (la del panel, no la de FTP).

---

## 2. Usar Cursor / VS Code con Remote SSH

Para abrir el servidor directamente en Cursor y editar archivos por SSH:

1. Añade este bloque a tu archivo de configuración SSH:
   - **Windows:** `C:\Users\TU_USUARIO\.ssh\config`
   - **Mac/Linux:** `~/.ssh/config`

   Si no existe el archivo, créalo. Contenido:

   ```
   Host hostinger-vcf
       HostName 31.170.166.193
       User u766140586
       Port 65002
   ```

2. En Cursor: **Ctrl+Shift+P** (o Cmd+Shift+P en Mac) → escribe **Remote-SSH: Connect to Host**.
3. Elige **hostinger-vcf** e introduce la contraseña cuando la pida.
4. Se abrirá una ventana conectada al servidor. Abre la carpeta **domains/vcfacademyhouston.com/public_html** (o la ruta que Hostinger indique como raíz del sitio).

En el proyecto hay un archivo de ejemplo: **docs/ssh-config-hostinger.txt**. Puedes copiar su contenido a tu `~/.ssh/config` o `C:\Users\...\.ssh\config`.

---

## 3. Ruta del sitio en el servidor

En Hostinger, los archivos de la web suelen estar en:

- **Ruta típica:** `~/domains/vcfacademyhouston.com/public_html`
- O solo: `~/public_html`

Al conectarte por SSH, compruébalo con:

```bash
pwd
ls
```

Si ves `public_html`, esa es la carpeta donde deben estar los archivos del sitio (index.php, admin/, assets/, config/, etc.).

---

## 4. Subir archivos desde tu PC por SSH/SCP

Desde **PowerShell o terminal en tu PC** (no dentro de la sesión SSH), en la carpeta del proyecto:

**Subir todo el sitio (excluyendo config local y .git):**

```powershell
scp -P 65002 -r admin assets config includes api sql index.php join.php contact.php calendar.php privacy.php deploy.php scripts u766140586@31.170.166.193:domains/vcfacademyhouston.com/public_html/
```

(Ajusta la ruta después de los `:` si en tu servidor es distinta, por ejemplo `public_html` directamente.)

En **Linux/Mac** el parámetro del puerto es `-P` también en `scp`. Si usas **rsync**:

```bash
rsync -avz -e "ssh -p 65002" --exclude '.git' --exclude 'config/database.local.php' --exclude 'config/deploy-credentials.php' --exclude 'valencia-website.zip' --exclude 'export/' ./ u766140586@31.170.166.193:domains/vcfacademyhouston.com/public_html/
```

---

## 5. Después de subir

1. **Base de datos:** Crea la BD en el panel de Hostinger (Bases de datos MySQL), importa tu `.sql` desde phpMyAdmin de Hostinger.
2. **Configuración:** En el servidor crea o edita `config/database.local.php` con el host, nombre de BD, usuario y contraseña que te dé Hostinger (usa `config/database.hostinger.example.php` como plantilla).
3. **Permisos:** En SSH, en la carpeta del sitio:
   ```bash
   chmod -R 755 assets/uploads
   ```

---

## Resumen

- **Conectar por terminal:** `ssh -p 65002 u766140586@31.170.166.193`
- **Conectar desde Cursor:** Añade el bloque `Host hostinger-vcf` a tu `.ssh/config` y usa **Remote-SSH: Connect to Host** → **hostinger-vcf**.
- **Ruta del sitio en el servidor:** `domains/vcfacademyhouston.com/public_html` o `public_html`.
