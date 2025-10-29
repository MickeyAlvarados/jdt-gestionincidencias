# Guía de Despliegue en Producción - Ubuntu Server

Sistema de gestión de incidencias técnicas con chat IA automatizado - **JDT-GestiónIncidencias**

## Stack de Producción

- **OS**: Ubuntu Server 22.04/24.04 LTS
- **Web Server**: Nginx
- **PHP**: 8.3 (PHP-FPM)
- **Database**: PostgreSQL 17
- **Process Manager**: Supervisor
- **SSL**: Let's Encrypt (Certbot)

---

## 1. Requisitos del Sistema

### Hardware Mínimo Recomendado
- **CPU**: 2 cores
- **RAM**: 4 GB (mínimo 2 GB)
- **Disco**: 20 GB SSD
- **Ancho de banda**: 100 Mbps

### Software Base
- Ubuntu Server 22.04 LTS o superior
- Acceso root o sudo
- Dominio DNS configurado (ejemplo: incidencias.tudominio.com)

---

## 2. Instalación de Dependencias del Sistema

### 2.1 Actualizar Sistema

```bash
sudo apt update && sudo apt upgrade -y
```

### 2.2 Instalar PHP 8.3 y Extensiones

```bash
# Agregar repositorio de PHP
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Instalar PHP 8.3 y extensiones necesarias
sudo apt install -y php8.3 \
    php8.3-fpm \
    php8.3-cli \
    php8.3-common \
    php8.3-pgsql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-bcmath \
    php8.3-curl \
    php8.3-gd \
    php8.3-zip \
    php8.3-redis \
    php8.3-intl

# Verificar instalación
php -v
# Debe mostrar: PHP 8.3.x
```

### 2.3 Instalar PostgreSQL 17

```bash
# Agregar repositorio oficial de PostgreSQL
sudo sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo apt-key add -
sudo apt update

# Instalar PostgreSQL 17
sudo apt install -y postgresql-17 postgresql-contrib-17

# Verificar servicio
sudo systemctl status postgresql
# Debe estar "active (running)"

# Verificar versión
psql --version
# Debe mostrar: psql (PostgreSQL) 17.x
```

### 2.4 Instalar Nginx

```bash
sudo apt install -y nginx

# Verificar instalación
nginx -v
sudo systemctl status nginx
```

### 2.5 Instalar Composer

```bash
# Descargar instalador
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# Verificar instalador (opcional)
php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"

# Instalar globalmente
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer

# Verificar
composer --version
```

### 2.6 Instalar Node.js y NPM

```bash
# Instalar NVM (Node Version Manager)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# Cargar NVM
source ~/.bashrc

# Instalar Node.js LTS (v20.x)
nvm install 20
nvm use 20

# Verificar
node -v  # Debe mostrar v20.x.x
npm -v   # Debe mostrar 10.x.x
```

### 2.7 Instalar Supervisor

```bash
sudo apt install -y supervisor

# Verificar
sudo systemctl status supervisor
```

---

## 3. Configuración de PostgreSQL

### 3.1 Crear Base de Datos y Usuario

```bash
# Cambiar a usuario postgres
sudo -u postgres psql

# En el prompt de PostgreSQL:
CREATE DATABASE jdt_gestionincidencias;
CREATE USER jdt_user WITH ENCRYPTED PASSWORD 'tu_password_seguro_aqui';
GRANT ALL PRIVILEGES ON DATABASE jdt_gestionincidencias TO jdt_user;

# PostgreSQL 15+: Conceder privilegios al schema público
\c jdt_gestionincidencias
GRANT ALL ON SCHEMA public TO jdt_user;
GRANT CREATE ON SCHEMA public TO jdt_user;

# Salir
\q
```

### 3.2 Configurar Acceso Remoto (Opcional)

Si necesitas acceso desde otros servidores:

```bash
# Editar postgresql.conf
sudo nano /etc/postgresql/17/main/postgresql.conf

# Cambiar:
listen_addresses = 'localhost'
# Por:
listen_addresses = '*'

# Editar pg_hba.conf
sudo nano /etc/postgresql/17/main/pg_hba.conf

# Agregar al final:
host    jdt_gestionincidencias    jdt_user    0.0.0.0/0    md5

# Reiniciar PostgreSQL
sudo systemctl restart postgresql
```

### 3.3 Optimizaciones de PostgreSQL (Producción)

```bash
sudo nano /etc/postgresql/17/main/postgresql.conf
```

**Ajustes recomendados para 4GB RAM:**

```ini
# Conexiones
max_connections = 100

# Memoria
shared_buffers = 1GB
effective_cache_size = 3GB
maintenance_work_mem = 256MB
checkpoint_completion_target = 0.9
wal_buffers = 16MB
default_statistics_target = 100
random_page_cost = 1.1
effective_io_concurrency = 200
work_mem = 10485kB
min_wal_size = 1GB
max_wal_size = 4GB
```

Reiniciar:
```bash
sudo systemctl restart postgresql
```

---

## 4. Configuración de PHP-FPM

### 4.1 Optimizar PHP-FPM

```bash
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

**Configuraciones importantes:**

```ini
[www]
user = www-data
group = www-data

; Modo de escucha (socket es más rápido que TCP)
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

; Procesos (ajustar según RAM)
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 500
```

### 4.2 Ajustar php.ini para Producción

```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

**Configuraciones recomendadas:**

```ini
; Límites
memory_limit = 512M
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300

; Errores (no mostrar en producción)
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php8.3-fpm-errors.log

; Seguridad
expose_php = Off
allow_url_fopen = On

; OPcache (muy importante para performance)
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
opcache.revalidate_freq = 0
opcache.save_comments = 1

; Timezone
date.timezone = America/Mexico_City
```

**Reiniciar PHP-FPM:**

```bash
sudo systemctl restart php8.3-fpm
sudo systemctl status php8.3-fpm
```

---

## 5. Desplegar la Aplicación Laravel

### 5.1 Crear Usuario de Aplicación (Seguridad)

```bash
# Crear usuario sin shell (más seguro)
sudo adduser --disabled-password --gecos "" laravel

# Agregar a grupo www-data
sudo usermod -aG www-data laravel
```

### 5.2 Clonar y Configurar Proyecto

```bash
# Cambiar a usuario laravel
sudo su - laravel

# Ir al directorio de aplicaciones
cd /var/www

# Clonar repositorio (o subir vía SFTP/Git)
git clone https://github.com/tu-usuario/jdt-gestionincidencias.git
cd jdt-gestionincidencias

# O si subes código manualmente:
# sudo mkdir -p /var/www/jdt-gestionincidencias
# sudo chown -R laravel:www-data /var/www/jdt-gestionincidencias
# Luego sube los archivos vía SFTP
```

### 5.3 Instalar Dependencias

```bash
# Instalar dependencias PHP (sin dev)
composer install --no-dev --optimize-autoloader

# Instalar dependencias Node.js
npm ci --production

# Compilar assets de producción
npm run build
```

### 5.4 Configurar Entorno

```bash
# Copiar archivo de entorno
cp .env.example .env

# Editar configuración
nano .env
```

**Configuración .env de producción:**

```env
APP_NAME="JDT Gestión Incidencias"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://incidencias.tudominio.com

LOG_CHANNEL=stack
LOG_LEVEL=error

# Base de datos PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=jdt_gestionincidencias
DB_USERNAME=jdt_user
DB_PASSWORD=tu_password_seguro_aqui

# Session y cache
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database

# Colas (importante para chat IA)
QUEUE_CONNECTION=database

# Broadcasting con Reverb
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST="0.0.0.0"
REVERB_PORT=8080
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="incidencias.tudominio.com"
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

# DeepSeek AI
DEEPSEEK_API_KEY=sk-tu_api_key_real_aqui
DEEPSEEK_API_URL=https://api.deepseek.com/v1
DEEPSEEK_MODEL=deepseek-chat
DEEPSEEK_MAX_TOKENS=1000
DEEPSEEK_TEMPERATURE=0.7
DEEPSEEK_TIMEOUT=30

# Mail (configurar con tu proveedor)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 5.5 Generar Claves y Configurar

```bash
# Generar APP_KEY
php artisan key:generate

# Generar credenciales Reverb
php generate-reverb-credentials.php
# Copia el output a tu .env

# Crear enlace simbólico de storage
php artisan storage:link

# Ejecutar migraciones y seeders
php artisan migrate --seed --force

# Optimizaciones de Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 5.6 Configurar Permisos

```bash
# Volver a usuario root
exit

# Establecer propietario
sudo chown -R laravel:www-data /var/www/jdt-gestionincidencias

# Permisos de directorios
sudo find /var/www/jdt-gestionincidencias -type d -exec chmod 755 {} \;

# Permisos de archivos
sudo find /var/www/jdt-gestionincidencias -type f -exec chmod 644 {} \;

# Permisos especiales para storage y bootstrap/cache
sudo chmod -R 775 /var/www/jdt-gestionincidencias/storage
sudo chmod -R 775 /var/www/jdt-gestionincidencias/bootstrap/cache

# SELinux (si está habilitado)
sudo chcon -R -t httpd_sys_rw_content_t /var/www/jdt-gestionincidencias/storage
sudo chcon -R -t httpd_sys_rw_content_t /var/www/jdt-gestionincidencias/bootstrap/cache
```

---

## 6. Configuración de Nginx

### 6.1 Crear Archivo de Configuración

```bash
sudo nano /etc/nginx/sites-available/jdt-gestionincidencias
```

**Configuración completa:**

```nginx
# Redirigir HTTP a HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name incidencias.tudominio.com;

    # Redirigir todo el tráfico a HTTPS
    return 301 https://$server_name$request_uri;
}

# Configuración HTTPS principal
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name incidencias.tudominio.com;

    # Directorio raíz (apuntar a /public de Laravel)
    root /var/www/jdt-gestionincidencias/public;
    index index.php index.html;

    # SSL (Certbot configurará estos automáticamente)
    # ssl_certificate /etc/letsencrypt/live/incidencias.tudominio.com/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/incidencias.tudominio.com/privkey.pem;

    # Configuración SSL segura
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Logs
    access_log /var/log/nginx/jdt-access.log;
    error_log /var/log/nginx/jdt-error.log;

    # Límites de carga
    client_max_body_size 50M;

    # Compresión Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/vnd.ms-fontobject image/svg+xml;

    # Seguridad: Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';" always;

    # Ocultar versión de Nginx
    server_tokens off;

    # Ubicación principal
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Procesar PHP
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;

        # Timeouts para operaciones largas
        fastcgi_read_timeout 300;
    }

    # WebSocket Reverse Proxy para Laravel Reverb
    location /app {
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_pass http://127.0.0.1:8080;

        # Timeouts para WebSocket
        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
    }

    # Cache de archivos estáticos
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Denegar acceso a archivos ocultos
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Denegar acceso a archivos sensibles
    location ~ /\.(env|git|htaccess|gitignore) {
        deny all;
    }
}
```

### 6.2 Habilitar Sitio

```bash
# Crear enlace simbólico
sudo ln -s /etc/nginx/sites-available/jdt-gestionincidencias /etc/nginx/sites-enabled/

# Desactivar sitio por defecto (opcional)
sudo rm /etc/nginx/sites-enabled/default

# Verificar sintaxis
sudo nginx -t

# Recargar Nginx
sudo systemctl reload nginx
```

---

## 7. Configurar SSL con Let's Encrypt

### 7.1 Instalar Certbot

```bash
sudo apt install -y certbot python3-certbot-nginx
```

### 7.2 Obtener Certificado

```bash
# Asegúrate de que tu dominio apunta a la IP del servidor
# Verifica: nslookup incidencias.tudominio.com

# Obtener certificado
sudo certbot --nginx -d incidencias.tudominio.com

# Seguir las instrucciones:
# 1. Ingresar email
# 2. Aceptar términos
# 3. Elegir opción 2: Redirect (forzar HTTPS)
```

### 7.3 Renovación Automática

```bash
# Certbot crea un timer automático, verificar:
sudo systemctl status certbot.timer

# Probar renovación
sudo certbot renew --dry-run
```

---

## 8. Configurar Supervisor (Queue Workers y Reverb)

### 8.1 Queue Worker

```bash
sudo nano /etc/supervisor/conf.d/jdt-queue-worker.conf
```

**Contenido:**

```ini
[program:jdt-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/jdt-gestionincidencias/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=laravel
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/jdt-gestionincidencias/storage/logs/queue-worker.log
stopwaitsecs=3600
```

### 8.2 Laravel Reverb (WebSockets)

```bash
sudo nano /etc/supervisor/conf.d/jdt-reverb.conf
```

**Contenido:**

```ini
[program:jdt-reverb]
process_name=%(program_name)s
command=php /var/www/jdt-gestionincidencias/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=laravel
redirect_stderr=true
stdout_logfile=/var/www/jdt-gestionincidencias/storage/logs/reverb.log
```

### 8.3 Activar Supervisor

```bash
# Recargar configuración
sudo supervisorctl reread
sudo supervisorctl update

# Iniciar todos los procesos
sudo supervisorctl start jdt-queue-worker:*
sudo supervisorctl start jdt-reverb:*

# Verificar estado
sudo supervisorctl status

# Debe mostrar:
# jdt-queue-worker:jdt-queue-worker_00   RUNNING
# jdt-queue-worker:jdt-queue-worker_01   RUNNING
# jdt-reverb:jdt-reverb                  RUNNING
```

### 8.4 Comandos Útiles de Supervisor

```bash
# Ver estado
sudo supervisorctl status

# Reiniciar un proceso
sudo supervisorctl restart jdt-reverb:*

# Detener un proceso
sudo supervisorctl stop jdt-queue-worker:*

# Ver logs en tiempo real
sudo tail -f /var/www/jdt-gestionincidencias/storage/logs/queue-worker.log
sudo tail -f /var/www/jdt-gestionincidencias/storage/logs/reverb.log
```

---

## 9. Configurar Tareas Programadas (Cron)

Laravel necesita un cron job para tareas programadas:

```bash
# Editar crontab del usuario laravel
sudo crontab -u laravel -e
```

**Agregar:**

```cron
* * * * * cd /var/www/jdt-gestionincidencias && php artisan schedule:run >> /dev/null 2>&1
```

---

## 10. Configuración de Firewall

### 10.1 UFW (Uncomplicated Firewall)

```bash
# Habilitar firewall
sudo ufw enable

# Permitir SSH (importante, hazlo primero)
sudo ufw allow 22/tcp

# Permitir HTTP y HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Permitir PostgreSQL (solo si acceso remoto necesario)
# sudo ufw allow 5432/tcp

# Ver reglas
sudo ufw status

# Debe mostrar:
# Status: active
#
# To                         Action      From
# --                         ------      ----
# 22/tcp                     ALLOW       Anywhere
# 80/tcp                     ALLOW       Anywhere
# 443/tcp                    ALLOW       Anywhere
```

---

## 11. Monitoreo y Logs

### 11.1 Logs de la Aplicación

```bash
# Logs de Laravel
tail -f /var/www/jdt-gestionincidencias/storage/logs/laravel.log

# Logs de Nginx
sudo tail -f /var/log/nginx/jdt-error.log
sudo tail -f /var/log/nginx/jdt-access.log

# Logs de PHP-FPM
sudo tail -f /var/log/php8.3-fpm-errors.log

# Logs de PostgreSQL
sudo tail -f /var/log/postgresql/postgresql-17-main.log

# Logs de Supervisor
sudo tail -f /var/www/jdt-gestionincidencias/storage/logs/queue-worker.log
sudo tail -f /var/www/jdt-gestionincidencias/storage/logs/reverb.log
```

### 11.2 Rotación de Logs

```bash
sudo nano /etc/logrotate.d/jdt-gestionincidencias
```

**Contenido:**

```
/var/www/jdt-gestionincidencias/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 laravel www-data
    sharedscripts
}
```

---

## 12. Backups Automatizados

### 12.1 Script de Backup de Base de Datos

```bash
sudo nano /usr/local/bin/backup-jdt-db.sh
```

**Contenido:**

```bash
#!/bin/bash

# Variables
BACKUP_DIR="/var/backups/jdt"
DB_NAME="jdt_gestionincidencias"
DB_USER="jdt_user"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz"

# Crear directorio si no existe
mkdir -p $BACKUP_DIR

# Realizar backup
PGPASSWORD='tu_password_seguro_aqui' pg_dump -U $DB_USER -h localhost $DB_NAME | gzip > $BACKUP_FILE

# Eliminar backups antiguos (más de 7 días)
find $BACKUP_DIR -type f -name "db_backup_*.sql.gz" -mtime +7 -delete

# Log
echo "Backup completado: $BACKUP_FILE" >> $BACKUP_DIR/backup.log
```

**Hacer ejecutable:**

```bash
sudo chmod +x /usr/local/bin/backup-jdt-db.sh
```

### 12.2 Programar Backup Diario

```bash
sudo crontab -e
```

**Agregar:**

```cron
# Backup diario a las 2:00 AM
0 2 * * * /usr/local/bin/backup-jdt-db.sh
```

---

## 13. Actualizaciones y Mantenimiento

### 13.1 Actualizar Código de la Aplicación

```bash
# Cambiar a usuario laravel
sudo su - laravel
cd /var/www/jdt-gestionincidencias

# Modo de mantenimiento
php artisan down

# Actualizar código (Git)
git pull origin main

# Actualizar dependencias
composer install --no-dev --optimize-autoloader
npm ci --production
npm run build

# Ejecutar migraciones
php artisan migrate --force

# Limpiar y cachear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Salir de mantenimiento
php artisan up

# Reiniciar workers
exit  # Volver a root
sudo supervisorctl restart jdt-queue-worker:*
sudo supervisorctl restart jdt-reverb:*
```

### 13.2 Script de Actualización Automatizado

```bash
sudo nano /usr/local/bin/update-jdt-app.sh
```

**Contenido:**

```bash
#!/bin/bash

APP_DIR="/var/www/jdt-gestionincidencias"
LARAVEL_USER="laravel"

echo "Iniciando actualización de JDT GestiónIncidencias..."

# Cambiar a usuario laravel y directorio de app
cd $APP_DIR || exit

# Activar modo mantenimiento
sudo -u $LARAVEL_USER php artisan down

# Actualizar código
sudo -u $LARAVEL_USER git pull origin main

# Actualizar dependencias
sudo -u $LARAVEL_USER composer install --no-dev --optimize-autoloader
sudo -u $LARAVEL_USER npm ci --production
sudo -u $LARAVEL_USER npm run build

# Migraciones
sudo -u $LARAVEL_USER php artisan migrate --force

# Optimizaciones
sudo -u $LARAVEL_USER php artisan config:cache
sudo -u $LARAVEL_USER php artisan route:cache
sudo -u $LARAVEL_USER php artisan view:cache
sudo -u $LARAVEL_USER php artisan event:cache

# Permisos
chown -R $LARAVEL_USER:www-data $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache

# Reiniciar servicios
supervisorctl restart jdt-queue-worker:*
supervisorctl restart jdt-reverb:*

# Salir de modo mantenimiento
sudo -u $LARAVEL_USER php artisan up

echo "Actualización completada"
```

**Hacer ejecutable:**

```bash
sudo chmod +x /usr/local/bin/update-jdt-app.sh
```

---

## 14. Seguridad Adicional

### 14.1 Fail2Ban (Protección contra fuerza bruta)

```bash
sudo apt install -y fail2ban

# Crear configuración personalizada
sudo nano /etc/fail2ban/jail.local
```

**Contenido:**

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true
port = http,https
logpath = /var/log/nginx/*error.log

[sshd]
enabled = true
port = 22
```

**Reiniciar:**

```bash
sudo systemctl restart fail2ban
sudo fail2ban-client status
```

### 14.2 Deshabilitar Root Login (SSH)

```bash
sudo nano /etc/ssh/sshd_config
```

**Cambiar:**

```
PermitRootLogin no
PasswordAuthentication no  # Usar solo llaves SSH
```

**Reiniciar SSH:**

```bash
sudo systemctl restart sshd
```

### 14.3 Actualizar Sistema Automáticamente

```bash
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```

---

## 15. Verificación Final del Sistema

### 15.1 Checklist de Producción

```bash
# 1. Verificar servicios
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status postgresql
sudo systemctl status supervisor

# 2. Verificar procesos Supervisor
sudo supervisorctl status

# 3. Verificar conectividad de base de datos
sudo -u laravel psql -U jdt_user -d jdt_gestionincidencias -c "SELECT 1;"

# 4. Verificar aplicación Laravel
cd /var/www/jdt-gestionincidencias
sudo -u laravel php artisan about

# 5. Verificar SSL
curl -I https://incidencias.tudominio.com

# 6. Verificar permisos
ls -la /var/www/jdt-gestionincidencias/storage
ls -la /var/www/jdt-gestionincidencias/bootstrap/cache

# 7. Ver logs en tiempo real
sudo tail -f /var/www/jdt-gestionincidencias/storage/logs/laravel.log
```

### 15.2 Test de Chat en Tiempo Real

1. Acceder a https://incidencias.tudominio.com
2. Login con admin@gmail.com / 123456
3. Ir a /chat
4. Enviar un mensaje de prueba
5. Verificar que la IA responde
6. Abrir consola del navegador (F12) y verificar que no hay errores de WebSocket

---

## 16. Troubleshooting Común

### Error: "Permission denied" en storage

```bash
sudo chmod -R 775 /var/www/jdt-gestionincidencias/storage
sudo chmod -R 775 /var/www/jdt-gestionincidencias/bootstrap/cache
sudo chown -R laravel:www-data /var/www/jdt-gestionincidencias
```

### Error: "502 Bad Gateway"

```bash
# Verificar PHP-FPM
sudo systemctl status php8.3-fpm
sudo systemctl restart php8.3-fpm

# Verificar logs
sudo tail -f /var/log/nginx/jdt-error.log
```

### Error: "WebSocket connection failed"

```bash
# Verificar que Reverb está corriendo
sudo supervisorctl status jdt-reverb

# Revisar logs
sudo tail -f /var/www/jdt-gestionincidencias/storage/logs/reverb.log

# Reiniciar
sudo supervisorctl restart jdt-reverb:*
```

### Jobs no se procesan

```bash
# Verificar queue workers
sudo supervisorctl status jdt-queue-worker

# Ver trabajos fallidos
cd /var/www/jdt-gestionincidencias
sudo -u laravel php artisan queue:failed

# Reintentar
sudo -u laravel php artisan queue:retry all

# Reiniciar workers
sudo supervisorctl restart jdt-queue-worker:*
```

### Error de conexión a PostgreSQL

```bash
# Verificar servicio
sudo systemctl status postgresql

# Verificar conexión
psql -U jdt_user -d jdt_gestionincidencias -h localhost

# Reiniciar
sudo systemctl restart postgresql
```

---

## 17. Recursos Adicionales

- **Laravel Deployment**: https://laravel.com/docs/11.x/deployment
- **Laravel Reverb**: https://laravel.com/docs/11.x/reverb
- **Nginx Optimization**: https://www.nginx.com/blog/tuning-nginx/
- **PostgreSQL Performance**: https://wiki.postgresql.org/wiki/Performance_Optimization
- **Supervisor**: http://supervisord.org/

---

## Soporte

Para problemas o dudas sobre el despliegue:

1. Revisar logs: `storage/logs/laravel.log`
2. Verificar servicios: `sudo supervisorctl status`
3. Contactar al equipo de desarrollo

---

**Desarrollado con Laravel 11 para la gestión eficiente de incidencias técnicas**
