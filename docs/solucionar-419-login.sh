#!/bin/bash

# Script para solucionar Error 419 en Login
# Uso: sudo bash solucionar-419-login.sh

echo "================================================"
echo "Solucionando Error 419 - Page Expired en Login"
echo "================================================"
echo ""

# Verificar que se ejecuta como root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Error: Este script debe ejecutarse con sudo"
    echo "Uso: sudo bash solucionar-419-login.sh"
    exit 1
fi

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

PROJECT_DIR="/var/www/jungledev/jdt/jdt-gestionincidencias"

if [ ! -f "$PROJECT_DIR/artisan" ]; then
    echo -e "${RED}Error: No se encuentra el proyecto en $PROJECT_DIR${NC}"
    exit 1
fi

cd $PROJECT_DIR

echo -e "${YELLOW}[1/9] Verificando archivo .env${NC}"
if ! grep -q "^SESSION_DOMAIN=gestionincidentes.jungledevperu.com" .env; then
    echo -e "${RED}✗ SESSION_DOMAIN no está configurado correctamente${NC}"
    echo "Agregando SESSION_DOMAIN al .env..."

    # Hacer backup
    cp .env .env.backup-$(date +%Y%m%d-%H%M%S)

    # Actualizar SESSION_DOMAIN
    sed -i 's/^SESSION_DOMAIN=.*/SESSION_DOMAIN=gestionincidentes.jungledevperu.com/' .env
    echo -e "${GREEN}✓ SESSION_DOMAIN actualizado${NC}"
else
    echo -e "${GREEN}✓ SESSION_DOMAIN correcto${NC}"
fi
echo ""

echo -e "${YELLOW}[2/9] Limpiando TODOS los cachés${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Cachés limpiados${NC}"
echo ""

echo -e "${YELLOW}[3/9] Verificando APP_KEY${NC}"
APP_KEY=$(grep "^APP_KEY=" .env | cut -d '=' -f2)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ] || [ "$APP_KEY" = "base64:" ]; then
    echo -e "${YELLOW}Generando APP_KEY...${NC}"
    php artisan key:generate --force
    echo -e "${GREEN}✓ APP_KEY generado${NC}"
else
    echo -e "${GREEN}✓ APP_KEY existe${NC}"
fi
echo ""

echo -e "${YELLOW}[4/9] Verificando y creando tabla de sesiones${NC}"
php artisan migrate:status | grep -q "sessions"
if [ $? -ne 0 ]; then
    echo -e "${YELLOW}Creando tabla de sesiones...${NC}"
    php artisan session:table
    php artisan migrate --force
    echo -e "${GREEN}✓ Tabla de sesiones creada${NC}"
else
    echo -e "${GREEN}✓ Tabla de sesiones existe${NC}"
fi
echo ""

echo -e "${YELLOW}[5/9] Limpiando sesiones antiguas de la base de datos${NC}"
php artisan tinker --execute="DB::table('sessions')->truncate();" 2>/dev/null || echo "No se pudo limpiar (tabla puede no existir aún)"
echo -e "${GREEN}✓ Sesiones limpiadas${NC}"
echo ""

echo -e "${YELLOW}[6/9] Corrigiendo permisos de archivos${NC}"
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Asegurar que storage/framework/sessions existe
mkdir -p storage/framework/sessions
chown -R www-data:www-data storage/framework/sessions
chmod -R 775 storage/framework/sessions

echo -e "${GREEN}✓ Permisos corregidos${NC}"
echo ""

echo -e "${YELLOW}[7/9] Regenerando caché de configuración${NC}"
php artisan config:cache
echo -e "${GREEN}✓ Caché de configuración regenerado${NC}"
echo ""

echo -e "${YELLOW}[8/9] Reiniciando servicios${NC}"
systemctl restart php8.3-fpm
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ PHP-FPM reiniciado${NC}"
else
    echo -e "${RED}✗ Error al reiniciar PHP-FPM${NC}"
fi

systemctl reload nginx
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Nginx recargado${NC}"
else
    echo -e "${RED}✗ Error al recargar Nginx${NC}"
fi
echo ""

echo -e "${YELLOW}[9/9] Verificación final${NC}"
echo "SESSION_DOMAIN: $(grep "^SESSION_DOMAIN=" .env | cut -d '=' -f2)"
echo "SESSION_DRIVER: $(grep "^SESSION_DRIVER=" .env | cut -d '=' -f2)"
echo "APP_URL: $(grep "^APP_URL=" .env | cut -d '=' -f2)"
echo ""
echo "Permisos de storage:"
ls -ld storage/ storage/framework/ storage/framework/sessions/ 2>/dev/null
echo ""

echo "================================================"
echo -e "${GREEN}✅ Proceso completado${NC}"
echo "================================================"
echo ""
echo "PRÓXIMOS PASOS:"
echo ""
echo "1. Abre tu navegador en MODO INCÓGNITO"
echo "   - Chrome: Ctrl+Shift+N"
echo "   - Firefox: Ctrl+Shift+P"
echo ""
echo "2. Ve a: http://gestionincidentes.jungledevperu.com"
echo ""
echo "3. Abre DevTools (F12) > Pestaña 'Network'"
echo ""
echo "4. Intenta hacer login"
echo ""
echo "5. Verifica la petición POST:"
echo "   - Si ves Status 200: ✅ Problema resuelto"
echo "   - Si ves Status 419: ❌ Revisar logs"
echo ""
echo "Si sigue fallando, revisar logs:"
echo "  tail -f storage/logs/laravel.log"
echo ""
echo "También verifica en el navegador (F12 > Application > Cookies):"
echo "  - Debe existir una cookie llamada: laravel_session"
echo "  - Domain debe ser: gestionincidentes.jungledevperu.com"
echo ""
