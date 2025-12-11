# Guía de Despliegue en Producción - Ubuntu Server

Sistema de gestión de incidencias técnicas con chat IA automatizado - **JDT-GestiónIncidencias**

**Nota**: Esta guía asume que ya tienes instalado PHP 8.3, PostgreSQL 17, Nginx, Composer y Node.js en tu servidor Ubuntu.

## ⚠️ IMPORTANTE: Configuración de WebSocket

Si ves el error `WebSocket connection to 'ws://0.0.0.0:8080' failed` en la consola del navegador, ve directamente a la sección **8. Troubleshooting > ERROR: WebSocket connection to 'ws://0.0.0.0:8080' failed** para solucionarlo.

**Regla de oro:** Las variables `VITE_REVERB_*` NUNCA deben contener `localhost`, `127.0.0.1` o `0.0.0.0`. Siempre deben tener tu dominio público real (ejemplo: `gestionincidentes.jungledevperu.com`).

---

## 1. Configurar Base de Datos PostgreSQL

```bash
# Conectar a PostgreSQL
sudo -u postgres psql

# Crear base de datos y usuario
CREATE DATABASE jdt_gestionincidencias;
CREATE USER jdt_user WITH ENCRYPTED PASSWORD 'tu_password_seguro';
GRANT ALL PRIVILEGES ON DATABASE jdt_gestionincidencias TO jdt_user;

# Dar permisos al schema
\c jdt_gestionincidencias
GRANT ALL ON SCHEMA public TO jdt_user;
GRANT CREATE ON SCHEMA public TO jdt_user;

# Salir
\q
```

---

## 2. Desplegar Aplicación Laravel

### 2.1 Subir Código al Servidor

```bash
# Crear directorio
sudo mkdir -p /var/www/jdt-gestionincidencias
sudo chown -R $USER:www-data /var/www/jdt-gestionincidencias

# Subir código (usa SFTP, rsync o git clone)
cd /var/www
git clone https://github.com/tu-usuario/jdt-gestionincidencias.git
cd jdt-gestionincidencias
```

### 2.2 Instalar Dependencias

```bash
# Dependencias PHP
composer install --no-dev --optimize-autoloader

# Dependencias Node.js y compilar assets
npm ci --production
npm run build
```

### 2.3 Configurar Entorno

```bash
# Copiar y editar .env
cp .env.example .env
nano .env
```

**Configurar estas variables en `.env`:**

```env
APP_NAME="JDT Gestión Incidencias"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://gestionincidentes.jungledevperu.com

# Base de datos
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=jdt_gestionincidencias
DB_USERNAME=jdt_user
DB_PASSWORD=tu_password_seguro

# Colas y Broadcasting
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb

# ==============================================
# REVERB WEBSOCKET - CONFIGURACIÓN CRÍTICA
# ==============================================

# Credenciales generadas con php generate-reverb-credentials.php
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=

# SERVIDOR: Dirección donde el servidor Reverb ESCUCHA (interna)
# IMPORTANTE: Usar 0.0.0.0 para escuchar en todas las interfaces
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# CLIENTE: Host/Dominio que el NAVEGADOR usa para conectarse (pública)
# CRÍTICO: NUNCA usar 0.0.0.0, localhost o 127.0.0.1 aquí
# Debe ser tu dominio real o IP pública del servidor
REVERB_HOST="gestionincidentes.jungledevperu.com"
REVERB_PORT=80
REVERB_SCHEME=http

# Variables para el frontend (Vite) - SE COMPILAN EN EL JAVASCRIPT
# IMPORTANTE: Después de cambiar estas variables, ejecutar: npm run build
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="gestionincidentes.jungledevperu.com"
VITE_REVERB_PORT=80
VITE_REVERB_SCHEME=http

# DeepSeek AI
DEEPSEEK_API_KEY=sk-tu_api_key_aqui
DEEPSEEK_API_URL=https://api.deepseek.com/v1
DEEPSEEK_MODEL=deepseek-chat
```

### 2.4 Generar Claves y Configurar

```bash
# Generar APP_KEY
php artisan key:generate

# Generar credenciales Reverb
php generate-reverb-credentials.php
# Copiar el output al .env

# Crear enlace simbólico
php artisan storage:link

# Ejecutar migraciones
php artisan migrate --seed --force

# Optimizar Laravel
sudo php artisan config:cache
sudo php artisan route:cache
sudo php artisan view:cache
sudo php artisan event:cache
```

### 2.5 Configurar Permisos

```bash
sudo chown -R www-data:www-data /var/www/jdt-gestionincidencias
sudo chmod -R 775 /var/www/jdt-gestionincidencias/storage
sudo chmod -R 775 /var/www/jdt-gestionincidencias/bootstrap/cache
```

---

## 3. Configurar Nginx (Sin SSL)

```bash
sudo nano /etc/nginx/sites-available/jdt-gestionincidencias
```

**Contenido:**

```nginx
server {
    listen 80;
    server_name gestionincidentes.jungledevperu.com;

    root /var/www/jdt-gestionincidencias/public;
    index index.php;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # WebSocket Proxy para Reverb
    location /app {
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_pass http://127.0.0.1:8080;

        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

**Activar sitio:**

```bash
sudo ln -s /etc/nginx/sites-available/jdt-gestionincidencias /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## 4. Iniciar Servicios (Manual)

### 4.1 Iniciar Laravel Reverb

```bash
cd /var/www/jdt-gestionincidencias
nohup php artisan reverb:start --host=0.0.0.0 --port=8080 > storage/logs/reverb.log 2>&1 &
```

### 4.2 Iniciar Queue Worker

```bash
cd /var/www/jdt-gestionincidencias
nohup php artisan queue:work --sleep=3 --tries=3 > storage/logs/queue-worker.log 2>&1 &
```

### 4.3 Verificar que están corriendo

```bash
ps aux | grep "artisan reverb"
ps aux | grep "artisan queue:work"

```

---

## 5. Verificación Rápida

```bash
# Verificar configuración de Reverb
cd /var/www/jdt-gestionincidencias
./verify-reverb-config.sh

# Verificar que Nginx está OK
sudo systemctl status nginx

# Verificar que PostgreSQL está OK
sudo systemctl status postgresql

# Probar la aplicación
curl -I http://gestionincidentes.jungledevperu.com
```

---

## 6. Actualizar Código

```bash
cd /var/www/jdt-gestionincidencias

# Modo mantenimiento
php artisan down

# Actualizar código
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci --production
npm run build

# Actualizar base de datos
php artisan migrate --force

# Limpiar cachés
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Salir de mantenimiento
php artisan up

# Reiniciar servicios
pkill -f "artisan reverb"
pkill -f "artisan queue:work"
nohup php artisan reverb:start --host=0.0.0.0 --port=8080 > storage/logs/reverb.log 2>&1 &
nohup php artisan queue:work --sleep=3 --tries=3 > storage/logs/queue-worker.log 2>&1 &
```

---

## 7. Troubleshooting

### ERROR: WebSocket connection to 'ws://0.0.0.0:8080' failed

**Síntoma:** En la consola del navegador aparece el error:
```
WebSocket connection to 'ws://0.0.0.0:8080/app/...' failed
```

**Causa:** El navegador está intentando conectarse a `0.0.0.0`, que es una dirección inválida para clientes. Esto ocurre porque las variables `VITE_REVERB_HOST` tienen el valor incorrecto.

**Solución:**

```bash
cd /var/www/jdt-gestionincidencias

# Paso 1: Editar .env y cambiar las variables de Reverb
nano .env
```

**Configuración correcta para `gestionincidentes.jungledevperu.com` (sin SSL):**

```env
# SERVIDOR: Dirección donde Reverb escucha (interno)
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# CLIENTE: Dominio que el navegador usa (público)
REVERB_HOST="gestionincidentes.jungledevperu.com"
REVERB_PORT=80
REVERB_SCHEME=http

# Frontend (CRÍTICO: usar tu dominio real)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="gestionincidentes.jungledevperu.com"
VITE_REVERB_PORT=80
VITE_REVERB_SCHEME=http
```

```bash
# Paso 2: OBLIGATORIO - Reconstruir el frontend
npm run build

# Paso 3: Limpiar cachés
php artisan config:clear
php artisan cache:clear

# Paso 4: Reiniciar Reverb
pkill -f "artisan reverb"
nohup php artisan reverb:start --host=0.0.0.0 --port=8080 > storage/logs/reverb.log 2>&1 &

# Paso 5: Verificar en el navegador (F12 > Console)
# Ya NO debe aparecer el error de WebSocket
```

### WebSocket no conecta (otros errores)

```bash
# 1. Verificar que Reverb está corriendo
ps aux | grep reverb

# 2. Verificar puerto 8080
sudo netstat -tlnp | grep 8080

# 3. Verificar variables VITE en .env
grep VITE_REVERB .env

# 4. Si VITE_REVERB_HOST apunta a localhost o 0.0.0.0, cambiar a tu dominio
nano .env
# Cambiar VITE_REVERB_HOST="gestionincidentes.jungledevperu.com"
npm run build
pkill -f "artisan reverb"
nohup php artisan reverb:start --host=0.0.0.0 --port=8080 > storage/logs/reverb.log 2>&1 &
```

### Chat IA no responde

```bash
# 1. Verificar Queue Worker
ps aux | grep queue:work

# 2. Ver trabajos fallidos
php artisan queue:failed

# 3. Reiniciar Queue Worker
pkill -f "artisan queue:work"
nohup php artisan queue:work --sleep=3 --tries=3 > storage/logs/queue-worker.log 2>&1 &
```

### Error 502 Bad Gateway

```bash
# Verificar PHP-FPM
sudo systemctl status php8.3-fpm
sudo systemctl restart php8.3-fpm
```

---

## Comandos Útiles

```bash
# Detener servicios
pkill -f "artisan reverb"
pkill -f "artisan queue:work"

# Ver procesos corriendo
ps aux | grep artisan

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Limpiar todos los cachés
php artisan optimize:clear
```

---

**Desarrollado con Laravel 11**
