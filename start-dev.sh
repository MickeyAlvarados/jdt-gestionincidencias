#!/bin/bash

# Script de inicio rápido para el sistema de chat con IA
# Uso: ./start-dev.sh

echo "🚀 Iniciando Sistema de Chat con IA..."
echo ""

# Colores
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo "❌ Error: Este script debe ejecutarse desde la raíz del proyecto Laravel"
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
    kill $QUEUE_PID $REVERB_PID $SERVE_PID 2>/dev/null
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

echo ""
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Sistema de Chat con IA iniciado correctamente${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""
echo -e "${BLUE}📊 Servicios activos:${NC}"
echo -e "   • Queue Worker:  PID $QUEUE_PID"
echo -e "   • Reverb Server: PID $REVERB_PID (ws://localhost:8080)"
echo -e "   • Laravel App:   PID $SERVE_PID (http://localhost:8000)"
echo ""
echo -e "${BLUE}📝 Logs disponibles en:${NC}"
echo -e "   • storage/logs/queue.log"
echo -e "   • storage/logs/reverb.log"
echo -e "   • storage/logs/serve.log"
echo ""
echo -e "${YELLOW}💡 Presiona Ctrl+C para detener todos los servicios${NC}"
echo ""

# Mantener el script corriendo
wait
