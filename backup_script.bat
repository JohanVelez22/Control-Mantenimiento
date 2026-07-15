@echo off
echo Iniciando respaldo de Control Mantenimiento Equipos...
set TIMESTAMP=%date:~6,4%%date:~3,2%%date:~0,2%_%time:~0,2%%time:~3,2%
set TIMESTAMP=%TIMESTAMP: =0%
set BACKUP_DIR=C:\Respaldo_Sistema
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

echo 1. Respaldando Base de Datos...
REM Cambia la ruta a donde este el mysqldump de ServBay
C:\ServBay\bin\mariadb\11.3\bin\mysqldump.exe -u root -pTuClaveSecreta nombre_base_datos > "%BACKUP_DIR%\db_respaldo_%TIMESTAMP%.sql"

echo 2. Respaldando Codigo Fuente...
REM Comprimir usando PowerShell (no requiere software extra)
powershell -command "Compress-Archive -Path 'C:\ServBay\www\control-mantenimiento-equipos\*' -DestinationPath '%BACKUP_DIR%\codigo_respaldo_%TIMESTAMP%.zip' -Force"

echo ­Respaldo completado en %BACKUP_DIR%! Si tienes la aplicacion de Google Drive instalada en Windows, simplemente configura %BACKUP_DIR% para que se sincronice automaticamente en la nube.
pause
