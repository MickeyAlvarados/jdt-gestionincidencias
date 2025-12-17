#!/bin/bash

# Script de inicio para desarrollo con Cloudflare Tunnel
# Uso: ./start-dev-cloudflare.sh

echo "🚀 Iniciando Sistema con Cloudflare Tunnel..."
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

# Verificar que cloudflared está instalado
if ! command -v cloudflared &> /dev/null; then
    echo -e "${RED}❌ Error: cloudflared no está instalado${NC}"
    echo -e "${YELLOW}Instala cloudflared desde: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/${NC}"
    exit 1
fi

# Verificar configuración
echo -e "${BLUE}📋 Verificando configuración...${NC}"

if ! grep -q "DEEPSEEK_API_KEY=sk-" .env; then
    echo -e "${YELLOW}⚠️  Advertencia: DEEPSEEK_API_KEY no configurada en .env${NC}"
    echo "   Obtén tu API key en: https://platform.deepseek.com/api_keys"
fi

if ! grep -q "REVERB_APP_KEY=" .env || [ -z "$(grep REVERB_APP_KEY= .env | cut -d'=' -f2)" ]; then
    echo -e "${YELLOW}⚠️  Configurando Reverb...${NC}"
    php artisan reverb:install
fi

echo -e "${GREEN}✅ Configuración verificada${NC}"
echo ""

# Función para matar procesos al salir
cleanup() {
    echo ""
    echo -e "${YELLOW}🛑 Deteniendo servicios...${NC}"
    kill $QUEUE_PID $REVERB_PID $SERVE_PID $TUNNEL_PID 2>/dev/null
    echo -e "${GREEN}✅ Servicios detenidos${NC}"
    exit 0
}

trap cleanup SIGINT SIGTERM

# Iniciar Queue Worker
echo -e "${BLUE}🔄 Iniciando Queue Worker...${NC}"
php artisan queue:work --tries=3 > storage/logs/queue.log 2>&1 &
QUEUE_PID=$!
echo -e "${GREEN}✅ Queue Worker iniciado (PID: $QUEUE_PID)${NC}"

# Esperar un momento
sleep 2

# Iniciar Reverb
echo -e "${BLUE}🌐 Iniciando Reverb WebSocket Server...${NC}"
php artisan reverb:start > storage/logs/reverb.log 2>&1 &
REVERB_PID=$!
echo -e "${GREEN}✅ Reverb iniciado en puerto 8080 (PID: $REVERB_PID)${NC}"

# Esperar un momento
sleep 2

# Iniciar Laravel Server
echo -e "${BLUE}🚀 Iniciando Laravel Server...${NC}"
php artisan serve > storage/logs/serve.log 2>&1 &
SERVE_PID=$!
echo -e "${GREEN}✅ Laravel Server iniciado en http://localhost:8000 (PID: $SERVE_PID)${NC}"

# Esperar un momento
sleep 3

# Iniciar Cloudflare Tunnel
echo -e "${BLUE}☁️  Iniciando Cloudflare Tunnel...${NC}"
cloudflared tunnel --url http://localhost:8000 > storage/logs/cloudflare-tunnel.log 2>&1 &
TUNNEL_PID=$!
echo -e "${GREEN}✅ Cloudflare Tunnel iniciado (PID: $TUNNEL_PID)${NC}"

# Esperar a que el túnel genere la URL
echo -e "${YELLOW}⏳ Esperando URL del túnel...${NC}"
sleep 5

# Intentar extraer la URL del log
TUNNEL_URL=$(grep -o 'https://[a-z0-9-]*\.trycloudflare\.com' storage/logs/cloudflare-tunnel.log | head -1)

echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Sistema iniciado correctamente con Cloudflare Tunnel${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${BLUE}📊 Servicios activos:${NC}"
echo -e "   • Queue Worker:  PID $QUEUE_PID"
echo -e "   • Reverb Server: PID $REVERB_PID (ws://localhost:8080)"
echo -e "   • Laravel App:   PID $SERVE_PID (http://localhost:8000)"
echo -e "   • Cloudflare:    PID $TUNNEL_PID"
echo ""

if [ -n "$TUNNEL_URL" ]; then
    echo -e "${GREEN}🌍 URL Pública: ${TUNNEL_URL}${NC}"
    echo ""
    echo -e "${YELLOW}⚠️  IMPORTANTE: Antes de acceder, actualiza tu .env:${NC}"
    echo ""
    echo -e "${BLUE}APP_ENV=production${NC}"
    echo -e "${BLUE}APP_DEBUG=false${NC}"
    echo -e "${BLUE}APP_URL=${TUNNEL_URL}${NC}"
    echo ""
    echo -e "${BLUE}REVERB_HOST=\"$(echo $TUNNEL_URL | sed 's|https://||')\"${NC}"
    echo -e "${BLUE}REVERB_PORT=443${NC}"
    echo -e "${BLUE}REVERB_SCHEME=https${NC}"
    echo ""
    echo -e "${BLUE}VITE_REVERB_HOST=\"$(echo $TUNNEL_URL | sed 's|https://||')\"${NC}"
    echo -e "${BLUE}VITE_REVERB_PORT=443${NC}"
    echo -e "${BLUE}VITE_REVERB_SCHEME=https${NC}"
    echo ""
    echo -e "${YELLOW}Luego ejecuta:${NC}"
    echo -e "${BLUE}php artisan config:clear && npm run build${NC}"
else
    echo -e "${YELLOW}⚠️  No se pudo obtener la URL del túnel automáticamente${NC}"
    echo -e "${YELLOW}Revisa el archivo: storage/logs/cloudflare-tunnel.log${NC}"
fi

echo ""
echo -e "${BLUE}📝 Logs disponibles en:${NC}"
echo -e "   • storage/logs/queue.log"
echo -e "   • storage/logs/reverb.log"
echo -e "   • storage/logs/serve.log"
echo -e "   • storage/logs/cloudflare-tunnel.log"
echo ""
echo -e "${YELLOW}💡 Presiona Ctrl+C para detener todos los servicios${NC}"
echo ""

# Mantener el script corriendo
wait
