#!/bin/bash

# Script para resolver Error 419 CSRF en producción
# Uso: ./fix-csrf-419.sh

echo "=========================================="
echo "Solucionando Error 419 CSRF en Producción"
echo "=========================================="
echo ""

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: No se encuentra el archivo artisan. Ejecuta este script desde el directorio raíz de Laravel.${NC}"
    exit 1
fi

echo -e "${YELLOW}[1/8] Verificando archivo .env...${NC}"
if grep -q "SESSION_DOMAIN=" .env; then
    echo -e "${GREEN}✓ SESSION_DOMAIN encontrado en .env${NC}"
else
    echo -e "${RED}✗ SESSION_DOMAIN no encontrado en .env${NC}"
    echo "Por favor, agrega la siguiente línea a tu .env:"
    echo "SESSION_DOMAIN=gestionincidentes.jungledevperu.com"
    exit 1
fi

echo ""
echo -e "${YELLOW}[2/8] Limpiando cachés...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Cachés limpiados${NC}"

echo ""
echo -e "${YELLOW}[3/8] Regenerando caché de configuración...${NC}"
php artisan config:cache
echo -e "${GREEN}✓ Caché de configuración regenerado${NC}"

echo ""
echo -e "${YELLOW}[4/8] Verificando tabla de sesiones...${NC}"
php artisan migrate:status | grep -q "sessions"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Tabla de sesiones existe${NC}"
else
    echo -e "${YELLOW}Creando tabla de sesiones...${NC}"
    php artisan session:table
    php artisan migrate --force
    echo -e "${GREEN}✓ Tabla de sesiones creada${NC}"
fi

echo ""
echo -e "${YELLOW}[5/8] Verificando permisos de storage...${NC}"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✓ Permisos corregidos${NC}"

echo ""
echo -e "${YELLOW}[6/8] Reiniciando PHP-FPM...${NC}"
sudo systemctl restart php8.3-fpm
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ PHP-FPM reiniciado${NC}"
else
    echo -e "${RED}✗ Error al reiniciar PHP-FPM${NC}"
fi

echo ""
echo -e "${YELLOW}[7/8] Recargando Nginx...${NC}"
sudo systemctl reload nginx
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Nginx recargado${NC}"
else
    echo -e "${RED}✗ Error al recargar Nginx${NC}"
fi

echo ""
echo -e "${YELLOW}[8/8] Verificando configuración actual...${NC}"
echo "SESSION_DRIVER: $(grep SESSION_DRIVER .env | cut -d '=' -f2)"
echo "SESSION_DOMAIN: $(grep SESSION_DOMAIN .env | cut -d '=' -f2)"
echo "SESSION_SECURE_COOKIE: $(grep SESSION_SECURE_COOKIE .env | cut -d '=' -f2)"
echo "APP_URL: $(grep APP_URL .env | cut -d '=' -f2)"

echo ""
echo -e "${GREEN}=========================================="
echo "✓ Proceso completado"
echo "==========================================${NC}"
echo ""
echo "Próximos pasos:"
echo "1. Abre una ventana de incógnito en tu navegador"
echo "2. Limpia las cookies del sitio (F12 > Application > Cookies)"
echo "3. Ve a tu sitio y prueba el chat"
echo ""
echo "Si el problema persiste, revisa los logs:"
echo "  tail -f storage/logs/laravel.log"
echo ""
