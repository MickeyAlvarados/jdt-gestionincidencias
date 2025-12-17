# Script de inicio para desarrollo con Cloudflare Tunnel (Windows)
# Uso: .\start-dev-cloudflare.ps1

Write-Host "🚀 Iniciando Sistema con Cloudflare Tunnel..." -ForegroundColor Cyan
Write-Host ""

# Verificar que estamos en el directorio correcto
if (-not (Test-Path "artisan")) {
    Write-Host "❌ Error: Este script debe ejecutarse desde la raíz del proyecto Laravel" -ForegroundColor Red
    exit 1
}

# Verificar que cloudflared está instalado
$cloudflaredExists = Get-Command cloudflared -ErrorAction SilentlyContinue
if (-not $cloudflaredExists) {
    Write-Host "❌ Error: cloudflared no está instalado" -ForegroundColor Red
    Write-Host "Instala cloudflared desde: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/" -ForegroundColor Yellow
    Write-Host "O usa: winget install --id Cloudflare.cloudflared" -ForegroundColor Yellow
    exit 1
}

# Verificar configuración
Write-Host "📋 Verificando configuración..." -ForegroundColor Blue

if (-not (Select-String -Path .env -Pattern "DEEPSEEK_API_KEY=sk-" -Quiet)) {
    Write-Host "⚠️  Advertencia: DEEPSEEK_API_KEY no configurada en .env" -ForegroundColor Yellow
    Write-Host "   Obtén tu API key en: https://platform.deepseek.com/api_keys"
}

$reverbKey = Select-String -Path .env -Pattern "REVERB_APP_KEY=" | Select-Object -First 1
if (-not $reverbKey -or $reverbKey -match "REVERB_APP_KEY=$") {
    Write-Host "⚠️  Configurando Reverb..." -ForegroundColor Yellow
    php artisan reverb:install
}

Write-Host "✅ Configuración verificada" -ForegroundColor Green
Write-Host ""

# Array para almacenar los procesos
$global:processes = @()

# Función para limpiar procesos al salir
function Cleanup {
    Write-Host ""
    Write-Host "🛑 Deteniendo servicios..." -ForegroundColor Yellow
    foreach ($proc in $global:processes) {
        if ($proc -and -not $proc.HasExited) {
            Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
        }
    }
    Write-Host "✅ Servicios detenidos" -ForegroundColor Green
    exit 0
}

# Registrar el manejador de Ctrl+C
$null = Register-EngineEvent -SourceIdentifier PowerShell.Exiting -Action { Cleanup }
[Console]::TreatControlCAsInput = $false

# Crear directorio de logs si no existe
if (-not (Test-Path "storage/logs")) {
    New-Item -ItemType Directory -Path "storage/logs" -Force | Out-Null
}

try {
    # Iniciar Queue Worker
    Write-Host "🔄 Iniciando Queue Worker..." -ForegroundColor Blue
    $queueProcess = Start-Process -FilePath "php" -ArgumentList "artisan", "queue:work", "--tries=3" `
        -RedirectStandardOutput "storage/logs/queue.log" `
        -RedirectStandardError "storage/logs/queue-error.log" `
        -NoNewWindow -PassThru
    $global:processes += $queueProcess
    Write-Host "✅ Queue Worker iniciado (PID: $($queueProcess.Id))" -ForegroundColor Green
    Start-Sleep -Seconds 2

    # Iniciar Reverb
    Write-Host "🌐 Iniciando Reverb WebSocket Server..." -ForegroundColor Blue
    $reverbProcess = Start-Process -FilePath "php" -ArgumentList "artisan", "reverb:start" `
        -RedirectStandardOutput "storage/logs/reverb.log" `
        -RedirectStandardError "storage/logs/reverb-error.log" `
        -NoNewWindow -PassThru
    $global:processes += $reverbProcess
    Write-Host "✅ Reverb iniciado en puerto 8080 (PID: $($reverbProcess.Id))" -ForegroundColor Green
    Start-Sleep -Seconds 2

    # Iniciar Laravel Server
    Write-Host "🚀 Iniciando Laravel Server..." -ForegroundColor Blue
    $serveProcess = Start-Process -FilePath "php" -ArgumentList "artisan", "serve" `
        -RedirectStandardOutput "storage/logs/serve.log" `
        -RedirectStandardError "storage/logs/serve-error.log" `
        -NoNewWindow -PassThru
    $global:processes += $serveProcess
    Write-Host "✅ Laravel Server iniciado en http://localhost:8000 (PID: $($serveProcess.Id))" -ForegroundColor Green
    Start-Sleep -Seconds 3

    # Iniciar Cloudflare Tunnel
    Write-Host "☁️  Iniciando Cloudflare Tunnel..." -ForegroundColor Blue
    $tunnelProcess = Start-Process -FilePath "cloudflared" -ArgumentList "tunnel", "--url", "http://localhost:8000" `
        -RedirectStandardOutput "storage/logs/cloudflare-tunnel.log" `
        -RedirectStandardError "storage/logs/cloudflare-tunnel-error.log" `
        -NoNewWindow -PassThru
    $global:processes += $tunnelProcess
    Write-Host "✅ Cloudflare Tunnel iniciado (PID: $($tunnelProcess.Id))" -ForegroundColor Green

    # Esperar a que el túnel genere la URL
    Write-Host "⏳ Esperando URL del túnel..." -ForegroundColor Yellow
    Start-Sleep -Seconds 5

    # Intentar extraer la URL del log
    $tunnelUrl = $null
    if (Test-Path "storage/logs/cloudflare-tunnel.log") {
        $logContent = Get-Content "storage/logs/cloudflare-tunnel.log" -Raw
        if ($logContent -match 'https://[a-z0-9-]+\.trycloudflare\.com') {
            $tunnelUrl = $matches[0]
        }
    }

    Write-Host ""
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green
    Write-Host "✅ Sistema iniciado correctamente con Cloudflare Tunnel" -ForegroundColor Green
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Green
    Write-Host ""
    Write-Host "📊 Servicios activos:" -ForegroundColor Blue
    Write-Host "   • Queue Worker:  PID $($queueProcess.Id)"
    Write-Host "   • Reverb Server: PID $($reverbProcess.Id) (ws://localhost:8080)"
    Write-Host "   • Laravel App:   PID $($serveProcess.Id) (http://localhost:8000)"
    Write-Host "   • Cloudflare:    PID $($tunnelProcess.Id)"
    Write-Host ""

    if ($tunnelUrl) {
        $tunnelHost = $tunnelUrl -replace 'https://', ''
        Write-Host "🌍 URL Pública: $tunnelUrl" -ForegroundColor Green
        Write-Host ""
        Write-Host "⚠️  IMPORTANTE: Antes de acceder, actualiza tu .env:" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "APP_ENV=production" -ForegroundColor Cyan
        Write-Host "APP_DEBUG=false" -ForegroundColor Cyan
        Write-Host "APP_URL=$tunnelUrl" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "REVERB_HOST=`"$tunnelHost`"" -ForegroundColor Cyan
        Write-Host "REVERB_PORT=443" -ForegroundColor Cyan
        Write-Host "REVERB_SCHEME=https" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "VITE_REVERB_HOST=`"$tunnelHost`"" -ForegroundColor Cyan
        Write-Host "VITE_REVERB_PORT=443" -ForegroundColor Cyan
        Write-Host "VITE_REVERB_SCHEME=https" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "Luego ejecuta:" -ForegroundColor Yellow
        Write-Host "php artisan config:clear && npm run build" -ForegroundColor Cyan
    } else {
        Write-Host "⚠️  No se pudo obtener la URL del túnel automáticamente" -ForegroundColor Yellow
        Write-Host "Revisa el archivo: storage/logs/cloudflare-tunnel.log" -ForegroundColor Yellow
    }

    Write-Host ""
    Write-Host "📝 Logs disponibles en:" -ForegroundColor Blue
    Write-Host "   • storage/logs/queue.log"
    Write-Host "   • storage/logs/reverb.log"
    Write-Host "   • storage/logs/serve.log"
    Write-Host "   • storage/logs/cloudflare-tunnel.log"
    Write-Host ""
    Write-Host "💡 Presiona Ctrl+C para detener todos los servicios" -ForegroundColor Yellow
    Write-Host ""

    # Mantener el script corriendo
    while ($true) {
        Start-Sleep -Seconds 1

        # Verificar si algún proceso crítico ha terminado
        foreach ($proc in $global:processes) {
            if ($proc.HasExited) {
                Write-Host "⚠️  Un servicio se ha detenido inesperadamente" -ForegroundColor Yellow
                Cleanup
            }
        }
    }
}
catch {
    Write-Host "❌ Error: $_" -ForegroundColor Red
    Cleanup
}
finally {
    Cleanup
}
