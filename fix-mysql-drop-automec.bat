@echo off
chcp 65001 >nul
echo Esperando 1 segundo a que MySQL acepte conexiones...
timeout /t 1 /nobreak >nul

"C:\xampp\mysql\bin\mysql.exe" -u root -e "DROP DATABASE IF EXISTS automec_db;"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo Base automec_db eliminada correctamente.
    echo Ahora: para MySQL en XAMPP, ejecuta fix-mysql-remove-recovery.bat y luego Start en MySQL.
    echo.
) else (
    echo.
    echo No se pudo conectar a MySQL. ¿Está MySQL en verde en XAMPP?
    echo Vuelve a intentar: Start MySQL, en cuanto esté en verde ejecuta este .bat de nuevo.
    echo.
)

pause
