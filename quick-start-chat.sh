#!/bin/bash

# Script de inicio rápido para el sistema de chat con IA
# Uso: ./quick-start-chat.sh

echo "=========================================="
echo "  Sistema de Chat con IA - Inicio Rápido"
echo "=========================================="
echo ""

# Verificar si existe el archivo .env
if [ ! -f .env ]; then
    echo "❌ Error: Archivo .env no encontrado"
    echo "Copia .env.example a .env y configúralo primero"
    exit 1
fi

# Verificar si REVERB_APP_KEY está configurado
if ! grep -q "REVERB_APP_KEY=.\+" .env; then
    echo "⚠️  REVERB_APP_KEY no está configurado"
    echo ""
    echo "Generando credenciales de Reverb..."
    php generate-reverb-credentials.php
    echo ""
    echo "⚠️  IMPORTANTE: Copia las credenciales generadas arriba en tu archivo .env"
    echo "Luego ejecuta este script nuevamente"
    exit 1
fi

# Verificar si DEEPSEEK_API_KEY está configurado
if ! grep -q "DEEPSEEK_API_KEY=.\+" .env || grep -q "DEEPSEEK_API_KEY=your_deepseek_api_key_here" .env; then
    echo "⚠️  Advertencia: DEEPSEEK_API_KEY no está configurado correctamente"
    echo "El chat funcionará pero la IA no podrá responder"
    echo "Obtén tu API key en: https://platform.deepseek.com/api_keys"
    echo ""
fi

echo "✅ Configuración verificada"
echo ""

# Limpiar caché
echo "🧹 Limpiando caché..."
php artisan config:clear > /dev/null 2>&1
php artisan cache:clear > /dev/null 2>&1
echo "✅ Caché limpiada"
echo ""

# Verificar si los assets están compilados
if [ ! -d "public/build" ]; then
    echo "⚠️  Assets no compilados. Ejecutando npm run build..."
    npm run build
    echo "✅ Assets compilados"
    echo ""
fi

echo "=========================================="
echo "  Iniciando servicios..."
echo "=========================================="
echo ""
echo "Este script abrirá 4 terminales:"
echo "  1. Servidor Laravel (puerto 8000)"
echo "  2. Servidor Reverb WebSocket (puerto 8080)"
echo "  3. Queue Worker (procesamiento de IA)"
echo "  4. Vite Dev Server (desarrollo frontend)"
echo ""
echo "Presiona Ctrl+C en cada terminal para detener los servicios"
echo ""
read -p "Presiona Enter para continuar..."

# Detectar el sistema operativo y abrir terminales
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    # Linux
    gnome-terminal --tab --title="Laravel Server" -- bash -c "php artisan serve; exec bash"
    gnome-terminal --tab --title="Reverb WebSocket" -- bash -c "php artisan reverb:start; exec bash"
    gnome-terminal --tab --title="Queue Worker" -- bash -c "php artisan queue:work --tries=3; exec bash"
    gnome-terminal --tab --title="Vite Dev" -- bash -c "npm run dev; exec bash"
elif [[ "$OSTYPE" == "darwin"* ]]; then
    # macOS
    osascript -e 'tell app "Terminal" to do script "cd '"$(pwd)"' && php artisan serve"'
    osascript -e 'tell app "Terminal" to do script "cd '"$(pwd)"' && php artisan reverb:start"'
    osascript -e 'tell app "Terminal" to do script "cd '"$(pwd)"' && php artisan queue:work --tries=3"'
    osascript -e 'tell app "Terminal" to do script "cd '"$(pwd)"' && npm run dev"'
else
    echo "⚠️  Sistema operativo no soportado para inicio automático"
    echo ""
    echo "Ejecuta manualmente en terminales separadas:"
    echo "  Terminal 1: php artisan serve"
    echo "  Terminal 2: php artisan reverb:start"
    echo "  Terminal 3: php artisan queue:work --tries=3"
    echo "  Terminal 4: npm run dev"
fi

echo ""
echo "=========================================="
echo "✅ Sistema iniciado"
echo "=========================================="
echo ""
echo "Accede al chat en: http://127.0.0.1:8000/chat"
echo ""
echo "Para detener todos los servicios, presiona Ctrl+C en cada terminal"
echo ""
