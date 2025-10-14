# Script de inicio rápido para el sistema de chat con IA (Windows)
# Uso: .\quick-start-chat.ps1

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  Sistema de Chat con IA - Inicio Rápido" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Verificar si existe el archivo .env
if (-not (Test-Path .env)) {
    Write-Host "❌ Error: Archivo .env no encontrado" -ForegroundColor Red
    Write-Host "Copia .env.example a .env y configúralo primero"
    exit 1
}

# Verificar si REVERB_APP_KEY está configurado
$envContent = Get-Content .env -Raw
if ($envContent -notmatch "REVERB_APP_KEY=.+") {
    Write-Host "⚠️  REVERB_APP_KEY no está configurado" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Generando credenciales de Reverb..."
    php generate-reverb-credentials.php
    Write-Host ""
    Write-Host "⚠️  IMPORTANTE: Copia las credenciales generadas arriba en tu archivo .env" -ForegroundColor Yellow
    Write-Host "Luego ejecuta este script nuevamente"
    exit 1
}

# Verificar si DEEPSEEK_API_KEY está configurado
if (($envContent -notmatch "DEEPSEEK_API_KEY=.+") -or ($envContent -match "DEEPSEEK_API_KEY=your_deepseek_api_key_here")) {
    Write-Host "⚠️  Advertencia: DEEPSEEK_API_KEY no está configurado correctamente" -ForegroundColor Yellow
    Write-Host "El chat funcionará pero la IA no podrá responder"
    Write-Host "Obtén tu API key en: https://platform.deepseek.com/api_keys"
    Write-Host ""
}

Write-Host "✅ Configuración verificada" -ForegroundColor Green
Write-Host ""

# Limpiar caché
Write-Host "🧹 Limpiando caché..."
php artisan config:clear | Out-Null
php artisan cache:clear | Out-Null
Write-Host "✅ Caché limpiada" -ForegroundColor Green
Write-Host ""

# Verificar si los assets están compilados
if (-not (Test-Path "public/build")) {
    Write-Host "⚠️  Assets no compilados. Ejecutando npm run build..." -ForegroundColor Yellow
    npm run build
    Write-Host "✅ Assets compilados" -ForegroundColor Green
    Write-Host ""
}

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "  Iniciando servicios..." -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Este script abrirá 4 ventanas de PowerShell:"
Write-Host "  1. Servidor Laravel (puerto 8000)"
Write-Host "  2. Servidor Reverb WebSocket (puerto 8080)"
Write-Host "  3. Queue Worker (procesamiento de IA)"
Write-Host "  4. Vite Dev Server (desarrollo frontend)"
Write-Host ""
Write-Host "Presiona Ctrl+C en cada ventana para detener los servicios"
Write-Host ""
Write-Host "Presiona Enter para continuar..."
$null = Read-Host

$currentPath = Get-Location

# Iniciar Laravel Server
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$currentPath'; Write-Host 'Servidor Laravel' -ForegroundColor Green; php artisan serve"

# Esperar un poco entre cada inicio
Start-Sleep -Seconds 1

# Iniciar Reverb WebSocket
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$currentPath'; Write-Host 'Servidor Reverb WebSocket' -ForegroundColor Green; php artisan reverb:start"

Start-Sleep -Seconds 1

# Iniciar Queue Worker
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$currentPath'; Write-Host 'Queue Worker' -ForegroundColor Green; php artisan queue:work --tries=3"

Start-Sleep -Seconds 1

# Iniciar Vite Dev Server
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$currentPath'; Write-Host 'Vite Dev Server' -ForegroundColor Green; npm run dev"

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "✅ Sistema iniciado" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Accede al chat en: http://127.0.0.1:8000/chat" -ForegroundColor Yellow
Write-Host ""
Write-Host "Para detener todos los servicios, presiona Ctrl+C en cada ventana de PowerShell"
Write-Host ""
Write-Host "Presiona Enter para cerrar esta ventana..."
$null = Read-Host
