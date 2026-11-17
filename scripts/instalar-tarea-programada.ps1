# Crea (o actualiza) la tarea de Windows que ejecuta el programador de Laravel cada minuto.
# Sin esta tarea no hay respaldos automáticos ni la verificación semanal del respaldo.
# Se lanza con doble clic en scripts\instalar-tarea-programada.bat (pide permisos de Administrador).
# Ver docs/auditoria-integral/GUIA_RESPALDOS.md, sección 5(a).

$ErrorActionPreference = 'Stop'

function Terminar([int] $codigo) {
    Write-Host ''
    Read-Host 'Presione Enter para cerrar'
    exit $codigo
}

try {
    $esAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
    if (-not $esAdmin) {
        Write-Host 'Este script necesita permisos de Administrador.' -ForegroundColor Red
        Write-Host 'Abralo con doble clic en instalar-tarea-programada.bat y acepte el aviso de Windows.'
        Terminar 1
    }

    $proyecto = Split-Path -Parent $PSScriptRoot
    $artisan = Join-Path $proyecto 'artisan'
    if (-not (Test-Path $artisan)) {
        Write-Host "No se encontró el archivo artisan en $proyecto." -ForegroundColor Red
        Terminar 1
    }

    $php = 'C:\ServBay\packages\php\current\php.exe'
    if (-not (Test-Path $php)) {
        $comando = Get-Command php.exe -ErrorAction SilentlyContinue
        if (-not $comando) {
            Write-Host 'No se encontró php.exe (ni en C:\ServBay\packages\php\current ni en el PATH).' -ForegroundColor Red
            Terminar 1
        }
        $php = $comando.Source
    }

    Write-Host 'Creando la tarea "TecniSystemas\Laravel Scheduler"...'
    Write-Host "  PHP:      $php"
    Write-Host "  Proyecto: $proyecto"

    # Cuenta SYSTEM: la misma con la que corre ServBay, así los archivos de storage tienen el mismo dueño
    # y la tarea funciona sin que nadie haya iniciado sesión. También corre con el portátil en batería.
    $accion = New-ScheduledTaskAction -Execute $php -Argument "`"$artisan`" schedule:run" -WorkingDirectory $proyecto
    $disparo = New-ScheduledTaskTrigger -Once -At (Get-Date).Date -RepetitionInterval (New-TimeSpan -Minutes 1)
    $ajustes = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -MultipleInstances IgnoreNew -ExecutionTimeLimit (New-TimeSpan -Minutes 30)
    $cuenta = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
    Register-ScheduledTask -TaskPath '\TecniSystemas\' -TaskName 'Laravel Scheduler' -Action $accion -Trigger $disparo -Settings $ajustes -Principal $cuenta -Description 'Respaldos automáticos y verificación semanal de TecniSystemas (php artisan schedule:run cada minuto).' -Force | Out-Null

    Write-Host 'Probando la tarea una vez...'
    Start-ScheduledTask -TaskPath '\TecniSystemas\' -TaskName 'Laravel Scheduler'
    $info = $null
    for ($i = 0; $i -lt 30; $i++) {
        Start-Sleep -Seconds 1
        $tarea = Get-ScheduledTask -TaskPath '\TecniSystemas\' -TaskName 'Laravel Scheduler'
        $info = Get-ScheduledTaskInfo -TaskPath '\TecniSystemas\' -TaskName 'Laravel Scheduler'
        if ($tarea.State -ne 'Running' -and $info.LastRunTime -gt (Get-Date).AddMinutes(-2)) { break }
    }

    if ($info -and $info.LastTaskResult -eq 0) {
        Write-Host ''
        Write-Host 'Listo: la tarea quedó creada y la prueba terminó bien.' -ForegroundColor Green
        Write-Host 'Desde ahora Windows ejecuta el programador cada minuto; los respaldos salen a la hora'
        Write-Host 'configurada en Configuración > Respaldos (el equipo debe estar encendido a esa hora).'
        Write-Host ''
        Write-Host 'Próximos trabajos programados:'
        & $php $artisan schedule:list
        Terminar 0
    }

    Write-Host ''
    Write-Host "La tarea quedó creada, pero la prueba devolvió el código $($info.LastTaskResult)." -ForegroundColor Yellow
    Write-Host 'Revise storage\logs\laravel.log o ejecute a mano:'
    Write-Host "  `"$php`" `"$artisan`" schedule:run"
    Terminar 2
}
catch {
    Write-Host ''
    Write-Host "No se pudo crear la tarea: $($_.Exception.Message)" -ForegroundColor Red
    Terminar 1
}
