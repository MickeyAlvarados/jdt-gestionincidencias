#!/bin/bash

# Script de inicio para producción del sistema de chat con IA
# Uso: ./start-prod.sh

echo "🚀 Iniciando Sistema de Chat con IA (Modo Producción)..."
echo ""

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: Este script debe ejecutarse desde la raíz del proyecto Laravel${NC}"
    exit 1
fi

# Verificar que estamos en modo producción
if ! grep -q "APP_ENV=production" .env; then
    echo -e "${YELLOW}⚠️  Advertencia: APP_ENV no está configurado como 'production' en .env${NC}"
    read -p "¿Continuar de todos modos? (s/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        exit 1
    fi
fi

# Verificar configuración
echo -e "${BLUE}📋 Verificando configuración...${NC}"

if [ ! -f ".env" ]; then
    echo -e "${RED}❌ Error: Archivo .env no encontrado${NC}"
    exit 1
fi

if ! grep -q "DEEPSEEK_API_KEY=sk-" .env; then
    echo -e "${RED}❌ Error: DEEPSEEK_API_KEY no configurada en .env${NC}"
    exit 1
fi

if ! grep -q "REVERB_APP_KEY=" .env || [ -z "$(grep REVERB_APP_KEY= .env | cut -d'=' -f2)" ]; then
    echo -e "${RED}❌ Error: REVERB_APP_KEY no configurada. Ejecuta: php generate-reverb-credentials.php${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Configuración verificada${NC}"
echo ""

# Optimizar aplicación para producción
echo -e "${BLUE}⚙️  Optimizando aplicación...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✅ Cachés optimizados${NC}"
echo ""

# Build del frontend
echo -e "${BLUE}📦 Construyendo assets del frontend...${NC}"
npm run build
echo -e "${GREEN}✅ Frontend construido${NC}"
echo ""

# Función para matar procesos al salir
cleanup() {
    echo ""
    echo -e "${YELLOW}🛑 Deteniendo servicios...${NC}"
    kill $QUEUE_PID $REVERB_PID 2>/dev/null
    echo -e "${GREEN}✅ Servicios detenidos${NC}"
    exit 0
}

trap cleanup SIGINT SIGTERM

# Iniciar Queue Worker (modo daemon)
echo -e "${BLUE}🔄 Iniciando Queue Worker (modo daemon)...${NC}"
php artisan queue:work --daemon --tries=3 --timeout=90 > storage/logs/queue-prod.log 2>&1 &
QUEUE_PID=$!
echo -e "${GREEN}✅ Queue Worker iniciado (PID: $QUEUE_PID)${NC}"
sleep 2

# Iniciar Reverb
echo -e "${BLUE}🌐 Iniciando Reverb WebSocket Server...${NC}"
php artisan reverb:start > storage/logs/reverb-prod.log 2>&1 &
REVERB_PID=$!
echo -e "${GREEN}✅ Reverb iniciado (PID: $REVERB_PID)${NC}"

echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Sistema de Chat con IA iniciado en modo PRODUCCIÓN${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${BLUE}📊 Servicios activos:${NC}"
echo -e "   • Queue Worker:  PID $QUEUE_PID (modo daemon)"
echo -e "   • Reverb Server: PID $REVERB_PID"
echo ""
echo -e "${BLUE}📝 Logs de producción en:${NC}"
echo -e "   • storage/logs/queue-prod.log"
echo -e "   • storage/logs/reverb-prod.log"
echo -e "   • storage/logs/laravel.log"
echo ""
echo -e "${YELLOW}⚠️  NOTA: Este script mantiene los procesos en foreground.${NC}"
echo -e "${YELLOW}   Para producción real, se recomienda usar Supervisor.${NC}"
echo -e "${YELLOW}   Ver: https://laravel.com/docs/11.x/queues#supervisor-configuration${NC}"
echo ""
echo -e "${YELLOW}💡 Presiona Ctrl+C para detener todos los servicios${NC}"
echo ""

# Mantener el script corriendo
wait
