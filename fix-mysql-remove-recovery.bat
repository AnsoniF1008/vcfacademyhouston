@echo off
chcp 65001 >nul
echo Quitando innodb_force_recovery del my.ini...
echo Asegúrate de haber ejecutado antes fix-mysql-drop-automec.bat con éxito.
echo MySQL debe estar parado (Stop en XAMPP) antes de ejecutar este script.
echo.
pause

powershell -NoProfile -Command "$p='C:\xampp\mysql\bin\my.ini'; (Get-Content $p -Encoding UTF8) | Where-Object { $_ -notmatch 'innodb_force_recovery' -and $_ -notmatch 'Modo recuperación.*automec_db' } | Set-Content $p -Encoding UTF8"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo Listo. innodb_force_recovery eliminado del my.ini.
    echo Ahora inicia MySQL en XAMPP (Start).
    echo.
) else (
    echo.
    echo Error al modificar my.ini. Edítalo a mano y borra las líneas de innodb_force_recovery.
    echo.
)

pause
