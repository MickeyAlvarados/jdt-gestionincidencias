#!/bin/bash

# Script de diagnóstico para Error 419 - Page Expired
# Uso: bash diagnosticar-419.sh

echo "================================================"
echo "Diagnóstico de Error 419 - Page Expired"
echo "================================================"
echo ""

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

PROJECT_DIR="/var/www/jungledev/jdt/jdt-gestionincidencias"

# Verificar que estamos en el directorio correcto
if [ ! -f "$PROJECT_DIR/artisan" ]; then
    echo -e "${RED}Error: No se encuentra el directorio del proyecto${NC}"
    echo "Verificando ruta: $PROJECT_DIR"
    exit 1
fi

cd $PROJECT_DIR

echo -e "${YELLOW}[1/10] Verificando variables de sesión en .env${NC}"
echo "SESSION_DRIVER: $(grep "^SESSION_DRIVER=" .env | cut -d '=' -f2)"
echo "SESSION_DOMAIN: $(grep "^SESSION_DOMAIN=" .env | cut -d '=' -f2)"
echo "SESSION_SECURE_COOKIE: $(grep "^SESSION_SECURE_COOKIE=" .env | cut -d '=' -f2)"
echo "SESSION_SAME_SITE: $(grep "^SESSION_SAME_SITE=" .env | cut -d '=' -f2)"
echo "APP_URL: $(grep "^APP_URL=" .env | cut -d '=' -f2)"
echo ""

if grep -q "^SESSION_DOMAIN=gestionincidentes.jungledevperu.com" .env; then
    echo -e "${GREEN}✓ SESSION_DOMAIN configurado correctamente${NC}"
else
    echo -e "${RED}✗ SESSION_DOMAIN no está configurado o es incorrecto${NC}"
    echo "Debe ser: SESSION_DOMAIN=gestionincidentes.jungledevperu.com"
fi
echo ""

echo -e "${YELLOW}[2/10] Verificando tabla de sesiones en la base de datos${NC}"
php artisan migrate:status | grep sessions
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Tabla de sesiones existe${NC}"
else
    echo -e "${RED}✗ Tabla de sesiones NO existe${NC}"
    echo "Ejecutar: php artisan session:table && php artisan migrate --force"
fi
echo ""

echo -e "${YELLOW}[3/10] Verificando permisos de storage${NC}"
ls -la storage/ | head -n 5
ls -la storage/framework/sessions/ 2>/dev/null
STORAGE_OWNER=$(stat -c '%U' storage 2>/dev/null || stat -f '%Su' storage 2>/dev/null)
echo "Propietario de storage/: $STORAGE_OWNER"
if [ "$STORAGE_OWNER" = "www-data" ]; then
    echo -e "${GREEN}✓ Permisos correctos${NC}"
else
    echo -e "${RED}✗ Permisos incorrectos (debería ser www-data)${NC}"
    echo "Ejecutar: sudo chown -R www-data:www-data storage bootstrap/cache"
fi
echo ""

echo -e "${YELLOW}[4/10] Verificando caché de configuración${NC}"
if [ -f bootstrap/cache/config.php ]; then
    echo "Archivo de caché encontrado. Verificando SESSION_DOMAIN..."
    grep -i "session.*domain" bootstrap/cache/config.php | head -n 3
    echo -e "${YELLOW}Si no aparece el dominio correcto, limpiar caché${NC}"
else
    echo -e "${YELLOW}No hay caché de configuración (esto puede ser el problema)${NC}"
fi
echo ""

echo -e "${YELLOW}[5/10] Verificando APP_KEY${NC}"
APP_KEY=$(grep "^APP_KEY=" .env | cut -d '=' -f2)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo -e "${RED}✗ APP_KEY no está configurado${NC}"
    echo "Ejecutar: php artisan key:generate"
else
    echo -e "${GREEN}✓ APP_KEY configurado${NC}"
fi
echo ""

echo -e "${YELLOW}[6/10] Verificando conexión a la base de datos${NC}"
php artisan db:show 2>&1 | head -n 10
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Conexión a base de datos OK${NC}"
else
    echo -e "${RED}✗ Error de conexión a base de datos${NC}"
fi
echo ""

echo -e "${YELLOW}[7/10] Verificando archivos de sesión (si SESSION_DRIVER=file)${NC}"
SESSION_DRIVER=$(grep "^SESSION_DRIVER=" .env | cut -d '=' -f2)
if [ "$SESSION_DRIVER" = "file" ]; then
    ls -la storage/framework/sessions/ | head -n 10
    echo "Total de archivos de sesión: $(ls storage/framework/sessions/ | wc -l)"
fi
echo ""

echo -e "${YELLOW}[8/10] Verificando logs recientes${NC}"
if [ -f storage/logs/laravel.log ]; then
    echo "Últimas líneas del log:"
    tail -n 20 storage/logs/laravel.log | grep -i "session\|csrf\|419" || echo "No se encontraron errores relacionados"
else
    echo "No hay archivo de log"
fi
echo ""

echo -e "${YELLOW}[9/10] Verificando estado de PHP-FPM${NC}"
sudo systemctl status php8.3-fpm --no-pager -l | head -n 10
echo ""

echo -e "${YELLOW}[10/10] Verificando configuración de Nginx${NC}"
sudo nginx -t
echo ""

echo "================================================"
echo "RESUMEN DE DIAGNÓSTICO"
echo "================================================"
echo ""
echo "Problemas detectados:"

# Verificar problemas
PROBLEMS=0

if ! grep -q "^SESSION_DOMAIN=gestionincidentes.jungledevperu.com" .env; then
    echo -e "${RED}1. SESSION_DOMAIN no configurado correctamente${NC}"
    PROBLEMS=$((PROBLEMS+1))
fi

if [ "$STORAGE_OWNER" != "www-data" ]; then
    echo -e "${RED}2. Permisos de storage incorrectos${NC}"
    PROBLEMS=$((PROBLEMS+1))
fi

if [ -z "$APP_KEY" ]; then
    echo -e "${RED}3. APP_KEY no configurado${NC}"
    PROBLEMS=$((PROBLEMS+1))
fi

if [ $PROBLEMS -eq 0 ]; then
    echo -e "${GREEN}No se detectaron problemas obvios${NC}"
    echo "El problema puede ser:"
    echo "- Caché de configuración desactualizado"
    echo "- Cookies bloqueadas en el navegador"
    echo "- Dominio incorrecto en el navegador"
fi

echo ""
echo "================================================"
echo "SOLUCIONES RECOMENDADAS"
echo "================================================"
echo ""
echo "Ejecuta los siguientes comandos en orden:"
echo ""
echo "# 1. Limpiar TODOS los cachés"
echo "php artisan config:clear"
echo "php artisan cache:clear"
echo "php artisan route:clear"
echo "php artisan view:clear"
echo ""
echo "# 2. Verificar/crear tabla de sesiones"
echo "php artisan session:table"
echo "php artisan migrate --force"
echo ""
echo "# 3. Corregir permisos"
echo "sudo chown -R www-data:www-data storage bootstrap/cache"
echo "sudo chmod -R 775 storage bootstrap/cache"
echo ""
echo "# 4. Regenerar caché de configuración"
echo "php artisan config:cache"
echo ""
echo "# 5. Reiniciar servicios"
echo "sudo systemctl restart php8.3-fpm"
echo "sudo systemctl reload nginx"
echo ""
echo "# 6. Probar en el navegador (modo incógnito)"
echo "# - Ctrl+Shift+N (Chrome) o Ctrl+Shift+P (Firefox)"
echo "# - Ir a: http://gestionincidentes.jungledevperu.com"
echo ""
