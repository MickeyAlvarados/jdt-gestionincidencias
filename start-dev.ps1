# Script de inicio rapido para el sistema de chat con IA (Windows)
# Uso: .\start-dev.ps1

Write-Host "Iniciando Sistema de Chat con IA..." -ForegroundColor Cyan
Write-Host ""

# Verificar que estamos en el directorio correcto
if (-not (Test-Path "artisan")) {
    Write-Host "Error: Este script debe ejecutarse desde la raiz del proyecto Laravel" -ForegroundColor Red
    exit 1
}

# Verificar configuracion
Write-Host "Verificando configuracion..." -ForegroundColor Blue

if (Test-Path .env) {
    $envContent = Get-Content .env -Raw

    if ($envContent -notmatch "DEEPSEEK_API_KEY=sk-") {
        Write-Host "Advertencia: DEEPSEEK_API_KEY no configurada en .env" -ForegroundColor Yellow
        Write-Host "Obten tu API key en: https://platform.deepseek.com/api_keys" -ForegroundColor Yellow
    }

    if ($envContent -notmatch "REVERB_APP_KEY=\w+" -or $envContent -match "REVERB_APP_KEY=$") {
        Write-Host "Configurando Reverb..." -ForegroundColor Yellow
        php artisan reverb:install
    }
} else {
    Write-Host "Error: Archivo .env no encontrado" -ForegroundColor Red
    exit 1
}

Write-Host "Configuracion verificada" -ForegroundColor Green
Write-Host ""

# Variables globales para los jobs
$script:queueJob = $null
$script:reverbJob = $null
$script:serveJob = $null

# Funcion para limpiar procesos al salir
function Stop-AllServices {
    Write-Host ""
    Write-Host "Deteniendo servicios..." -ForegroundColor Yellow

    if ($script:queueJob) {
        Stop-Job $script:queueJob -ErrorAction SilentlyContinue
        Remove-Job $script:queueJob -ErrorAction SilentlyContinue
    }
    if ($script:reverbJob) {
        Stop-Job $script:reverbJob -ErrorAction SilentlyContinue
        Remove-Job $script:reverbJob -ErrorAction SilentlyContinue
    }
    if ($script:serveJob) {
        Stop-Job $script:serveJob -ErrorAction SilentlyContinue
        Remove-Job $script:serveJob -ErrorAction SilentlyContinue
    }

    Write-Host "Servicios detenidos" -ForegroundColor Green
}

# Registrar limpieza al salir
Register-EngineEvent PowerShell.Exiting -Action { Stop-AllServices } | Out-Null

# Iniciar Queue Worker
Write-Host "Iniciando Queue Worker..." -ForegroundColor Blue
$script:queueJob = Start-Job -ScriptBlock {
    Set-Location $using:PWD
    php artisan queue:work --tries=3
}
Write-Host "Queue Worker iniciado (Job ID: $($script:queueJob.Id))" -ForegroundColor Green
Start-Sleep -Seconds 2

# Iniciar Reverb
Write-Host "Iniciando Reverb WebSocket Server..." -ForegroundColor Blue
$script:reverbJob = Start-Job -ScriptBlock {
    Set-Location $using:PWD
    php artisan reverb:start
}
Write-Host "Reverb iniciado en puerto 8080 (Job ID: $($script:reverbJob.Id))" -ForegroundColor Green
Start-Sleep -Seconds 2

# Iniciar Laravel Server
Write-Host "Iniciando Laravel Server..." -ForegroundColor Blue
$script:serveJob = Start-Job -ScriptBlock {
    Set-Location $using:PWD
    php artisan serve
}
Write-Host "Laravel Server iniciado en http://localhost:8000 (Job ID: $($script:serveJob.Id))" -ForegroundColor Green

Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host "Sistema de Chat con IA iniciado correctamente" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""
Write-Host "Servicios activos:" -ForegroundColor Blue
Write-Host "  - Queue Worker:  Job ID $($script:queueJob.Id)"
Write-Host "  - Reverb Server: Job ID $($script:reverbJob.Id) (ws://localhost:8080)"
Write-Host "  - Laravel App:   Job ID $($script:serveJob.Id) (http://localhost:8000)"
Write-Host ""
Write-Host "Ver logs de un servicio:" -ForegroundColor Blue
Write-Host "  Receive-Job -Id <Job ID> -Keep"
Write-Host ""
Write-Host "Presiona Ctrl+C para detener todos los servicios" -ForegroundColor Yellow
Write-Host ""

# Mantener el script corriendo y monitorear servicios
while ($true) {
    Start-Sleep -Seconds 5

    # Verificar estado de los jobs
    $queueState = (Get-Job -Id $script:queueJob.Id -ErrorAction SilentlyContinue).State
    $reverbState = (Get-Job -Id $script:reverbJob.Id -ErrorAction SilentlyContinue).State
    $serveState = (Get-Job -Id $script:serveJob.Id -ErrorAction SilentlyContinue).State

    if ($queueState -ne "Running" -or $reverbState -ne "Running" -or $serveState -ne "Running") {
        Write-Host ""
        Write-Host "Advertencia: Uno o mas servicios se detuvieron" -ForegroundColor Yellow
        Write-Host "  Queue Worker: $queueState"
        Write-Host "  Reverb Server: $reverbState"
        Write-Host "  Laravel Server: $serveState"
        Write-Host ""
        Write-Host "Ver logs con: Receive-Job -Id <Job ID>" -ForegroundColor Yellow
        Write-Host ""

        Stop-AllServices
        break
    }
}
