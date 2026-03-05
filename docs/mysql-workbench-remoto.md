# Conectar la base de datos remota con MySQL Workbench

## Datos de conexión (según tu captura)

| Parámetro         | Valor                                        |
| ----------------- | -------------------------------------------- |
| **Host / IP**     | `srv1144.hstgr.io` o `195.35.61.64`          |
| **Puerto**        | `3306` (estándar MySQL)                      |
| **Base de datos** | `u766140586_db_6K3gelNG`                     |
| **Usuario**       | `u766140586_usr_6K3gelNG`                    |
| **Acceso remoto** | Ya está permitido desde cualquier host (`%`) |

La contraseña es la que configuraste en el panel de Hostinger (MySQL). En el proyecto, para que la app use esta misma conexión desde tu PC, está guardada en `config/database.local.php` (ese archivo no se sube al repositorio).

---

## Pasos en MySQL Workbench

1. **Abrir MySQL Workbench** y, en la pantalla principal, hacer clic en el **+** junto a "MySQL Connections" (o **Database → Manage Connections**).
2. **Crear nueva conexión:**
   - **Connection Name:** nombre que quieras (ej: `vcfacademy remoto`).
   - **Connection Method:** `Standard (TCP/IP)`.
   - **Hostname:** `srv1144.hstgr.io` (o `195.35.61.64`).
   - **Port:** `3306`.
   - **Username:** `u766140586_usr_6K3gelNG`.
   - **Password:** clic en "Store in Vault…" e introduce la contraseña del usuario MySQL.
3. **Opcional:** en "Default Schema" selecciona `u766140586_db_6K3gelNG` para que al conectar se abra esa base por defecto.
4. **Test Connection:** pulsar **"Test Connection"**. Si pide la contraseña, introdúcela y confirma. Debe indicar que la conexión es correcta.
5. **Conectar:** doble clic en la nueva conexión (o seleccionarla y "OK") para abrir la sesión.

---

## Si la conexión falla

- **Firewall / antivirus:** comprobar que permiten salida por el puerto 3306.
- **Credenciales:** asegurarse de usar el **usuario MySQL** (no el del panel del hosting) y su contraseña.
- **Usuario remoto:** en el panel, en "MySQL remoto", ya tienes `%` para esa base; no hace falta añadir tu IP a menos que el hosting lo exija.
