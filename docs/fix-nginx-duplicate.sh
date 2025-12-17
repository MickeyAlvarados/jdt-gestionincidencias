#!/bin/bash

# Script para corregir el error de proxy_read_timeout duplicado en Nginx
# Uso: sudo bash fix-nginx-duplicate.sh

echo "================================================"
echo "Corrigiendo configuración duplicada de Nginx"
echo "================================================"
echo ""

# Verificar que se ejecuta como root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Error: Este script debe ejecutarse con sudo"
    echo "Uso: sudo bash fix-nginx-duplicate.sh"
    exit 1
fi

NGINX_FILE="/etc/nginx/sites-available/gestionincidentes.jungledevperu.com"
BACKUP_DIR="/etc/nginx/backups"

# Verificar que el archivo existe
if [ ! -f "$NGINX_FILE" ]; then
    echo "❌ Error: No se encuentra el archivo $NGINX_FILE"
    exit 1
fi

echo "✓ Archivo encontrado: $NGINX_FILE"
echo ""

# Crear directorio de backups si no existe
mkdir -p "$BACKUP_DIR"

# Crear backup con timestamp
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_FILE="$BACKUP_DIR/gestionincidentes.jungledevperu.com.backup-$TIMESTAMP"

echo "📦 Creando backup..."
cp "$NGINX_FILE" "$BACKUP_FILE"
echo "✓ Backup guardado en: $BACKUP_FILE"
echo ""

# Buscar líneas duplicadas de proxy_read_timeout
echo "🔍 Buscando directivas duplicadas..."
DUPLICATE_COUNT=$(grep -c "proxy_read_timeout" "$NGINX_FILE")

if [ "$DUPLICATE_COUNT" -gt 1 ]; then
    echo "⚠️  Encontradas $DUPLICATE_COUNT directivas 'proxy_read_timeout' (debería haber solo 1)"
    echo ""
    echo "Mostrando las líneas con proxy_read_timeout:"
    grep -n "proxy_read_timeout" "$NGINX_FILE"
    echo ""
else
    echo "✓ No se encontraron duplicados de proxy_read_timeout"
fi

# Mostrar contenido actual de la sección /app
echo "📄 Contenido actual de la sección WebSocket (location /app):"
echo "-----------------------------------------------------------"
sed -n '/location \/app/,/}/p' "$NGINX_FILE"
echo "-----------------------------------------------------------"
echo ""

# Preguntar si desea usar el archivo limpio
read -p "¿Deseas reemplazar con la configuración limpia? (s/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[SsYy]$ ]]; then
    # Verificar si existe el archivo limpio
    CLEAN_FILE="/tmp/nginx-LIMPIO.conf"

    if [ ! -f "$CLEAN_FILE" ]; then
        echo "❌ Error: No se encuentra el archivo $CLEAN_FILE"
        echo "Por favor, sube primero el archivo nginx-LIMPIO.conf a /tmp/"
        exit 1
    fi

    # Copiar el archivo limpio
    echo "📝 Aplicando configuración limpia..."
    cp "$CLEAN_FILE" "$NGINX_FILE"
    echo "✓ Archivo reemplazado"
    echo ""

    # Verificar la sintaxis
    echo "🧪 Verificando sintaxis de Nginx..."
    nginx -t

    if [ $? -eq 0 ]; then
        echo ""
        echo "✅ ¡Configuración correcta!"
        echo ""
        read -p "¿Deseas recargar Nginx ahora? (s/n): " -n 1 -r
        echo ""

        if [[ $REPLY =~ ^[SsYy]$ ]]; then
            systemctl reload nginx
            echo "✓ Nginx recargado exitosamente"
        else
            echo "⚠️  Recuerda recargar Nginx manualmente: sudo systemctl reload nginx"
        fi
    else
        echo ""
        echo "❌ Error en la configuración. Restaurando backup..."
        cp "$BACKUP_FILE" "$NGINX_FILE"
        echo "✓ Backup restaurado"
        echo "Por favor, revisa la configuración manualmente"
    fi
else
    echo "❌ Operación cancelada"
    echo "💡 Puedes editar el archivo manualmente y eliminar la línea duplicada:"
    echo "   sudo nano $NGINX_FILE"
fi

echo ""
echo "================================================"
echo "Lista de backups disponibles:"
ls -lh "$BACKUP_DIR"
echo "================================================"
