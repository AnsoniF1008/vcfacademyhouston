@echo off
chcp 65001 >nul
echo ============================================
echo   MySQL Recovery - DROP automec_db (auto)
echo ============================================
echo.
echo Asegúrate de que MySQL está PARADO en XAMPP (Stop).
echo Se abrirá una ventana con MySQL; no la cierres hasta que este script termine.
echo.
pause

echo Iniciando MySQL en ventana nueva...
start "MySQL Recovery" /D "C:\xampp\mysql\bin" mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini

echo Esperando 35 segundos a que MySQL acepte conexiones...
timeout /t 35 /nobreak >nul

set INTENTOS=0
:retry
set /a INTENTOS+=1
if %INTENTOS% GTR 45 goto fail

"C:\xampp\mysql\bin\mysql.exe" -h 127.0.0.1 -u root -e "DROP DATABASE IF EXISTS automec_db;"
if %ERRORLEVEL% EQU 0 goto ok

echo Intento %INTENTOS%/45 - MySQL aun no acepta conexiones. Esperando 2 segundos...
timeout /t 2 /nobreak >nul
goto retry

:ok
echo.
echo ============================================
echo   Base automec_db eliminada correctamente.
echo ============================================
echo.
echo Siguiente paso:
echo 1. Cierra la ventana "MySQL Recovery" que se abrió.
echo 2. Ejecuta fix-mysql-remove-recovery.bat
echo 3. En XAMPP pulsa Start en MySQL
echo.
goto end

:fail
echo.
echo Tras 45 intentos no se pudo conectar.
echo Cierra la ventana "MySQL Recovery".
echo Prueba poner innodb_force_recovery=4 en my.ini y ejecutar este script de nuevo,
echo o revisar los logs en C:\xampp\mysql\data\mysql_error.log
echo.
goto end

:end
pause
