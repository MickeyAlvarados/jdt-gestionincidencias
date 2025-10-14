# Script para solucionar el problema del chat
# Ejecutar con PowerShell: .\fix-chat.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  SOLUCION DEL ERROR: 'Error al iniciar el chat'" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "1. Limpiando cache de configuración..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear

Write-Host "`n2. Recreando base de datos con seeders corregidos..." -ForegroundColor Yellow
php artisan migrate:fresh --seed

Write-Host "`n3. Verificando usuario IA..." -ForegroundColor Yellow
php artisan tinker --execute="echo 'Usuario IA: '; print_r(App\Models\User::where('email', 'ia@support.local')->first()); echo '\n';"

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "  SOLUCION APLICADA CORRECTAMENTE" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Green

Write-Host "Ahora puedes:" -ForegroundColor White
Write-Host "  1. Iniciar los servicios: .\start-dev.ps1" -ForegroundColor White
Write-Host "  2. Acceder a: http://localhost:8000/chat" -ForegroundColor White
Write-Host "  3. Probar iniciar un chat" -ForegroundColor White
Write-Host "`nCredenciales de prueba:" -ForegroundColor Cyan
Write-Host "  Email: admin@gmail.com" -ForegroundColor White
Write-Host "  Password: 123456`n" -ForegroundColor White
