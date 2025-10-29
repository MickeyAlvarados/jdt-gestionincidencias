#!/bin/bash

###############################################################################
# Script de Instalación Automatizada - JDT GestiónIncidencias
# Para Ubuntu 22.04/24.04 LTS con PHP 8.3, Nginx y PostgreSQL 17
###############################################################################

set -e  # Salir si hay error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Funciones de utilidad
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}➜ $1${NC}"
}

# Verificar que se ejecuta como root
if [[ $EUID -ne 0 ]]; then
   print_error "Este script debe ejecutarse como root (sudo)"
   exit 1
fi

print_info "==================================================================="
print_info "Instalación de JDT GestiónIncidencias - Producción"
print_info "==================================================================="
echo ""

# Solicitar información al usuario
read -p "Ingrese el nombre de dominio (ej: incidencias.tudominio.com): " DOMAIN
read -p "Ingrese nombre de base de datos [jdt_gestionincidencias]: " DB_NAME
DB_NAME=${DB_NAME:-jdt_gestionincidencias}

read -p "Ingrese usuario de base de datos [jdt_user]: " DB_USER
DB_USER=${DB_USER:-jdt_user}

read -sp "Ingrese contraseña para base de datos: " DB_PASSWORD
echo ""

read -p "Ingrese API Key de DeepSeek: " DEEPSEEK_KEY

read -p "Ingrese email para SSL (Let's Encrypt): " SSL_EMAIL

echo ""
print_info "Iniciando instalación..."
sleep 2

###############################################################################
# 1. Actualizar sistema
###############################################################################
print_info "Actualizando sistema..."
apt update && apt upgrade -y
print_success "Sistema actualizado"

###############################################################################
# 2. Instalar dependencias básicas
###############################################################################
print_info "Instalando dependencias básicas..."
apt install -y software-properties-common curl wget git unzip supervisor ufw fail2ban
print_success "Dependencias básicas instaladas"

###############################################################################
# 3. Instalar PHP 8.3
###############################################################################
print_info "Instalando PHP 8.3..."
add-apt-repository ppa:ondrej/php -y
apt update

apt install -y php8.3 \
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

print_success "PHP 8.3 instalado: $(php -v | head -n 1)"

###############################################################################
# 4. Instalar PostgreSQL 17
###############################################################################
print_info "Instalando PostgreSQL 17..."
sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list'
wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add -
apt update
apt install -y postgresql-17 postgresql-contrib-17

systemctl start postgresql
systemctl enable postgresql

print_success "PostgreSQL 17 instalado"

###############################################################################
# 5. Configurar Base de Datos
###############################################################################
print_info "Configurando base de datos..."

sudo -u postgres psql <<EOF
CREATE DATABASE $DB_NAME;
CREATE USER $DB_USER WITH ENCRYPTED PASSWORD '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
\c $DB_NAME
GRANT ALL ON SCHEMA public TO $DB_USER;
GRANT CREATE ON SCHEMA public TO $DB_USER;
EOF

print_success "Base de datos configurada: $DB_NAME"

###############################################################################
# 6. Instalar Nginx
###############################################################################
print_info "Instalando Nginx..."
apt install -y nginx
systemctl start nginx
systemctl enable nginx
print_success "Nginx instalado"

###############################################################################
# 7. Instalar Composer
###############################################################################
print_info "Instalando Composer..."
cd /tmp
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
print_success "Composer instalado: $(composer --version | head -n 1)"

###############################################################################
# 8. Instalar Node.js 20 LTS
###############################################################################
print_info "Instalando Node.js..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
print_success "Node.js instalado: $(node -v), NPM: $(npm -v)"

###############################################################################
# 9. Crear usuario de aplicación
###############################################################################
print_info "Creando usuario 'laravel'..."
if ! id "laravel" &>/dev/null; then
    adduser --disabled-password --gecos "" laravel
    usermod -aG www-data laravel
    print_success "Usuario 'laravel' creado"
else
    print_info "Usuario 'laravel' ya existe"
fi

###############################################################################
# 10. Configurar directorio de aplicación
###############################################################################
print_info "Configurando directorio de aplicación..."

APP_DIR="/var/www/jdt-gestionincidencias"

if [ ! -d "$APP_DIR" ]; then
    print_error "El código de la aplicación no está en $APP_DIR"
    print_info "Por favor, sube tu código a $APP_DIR y vuelve a ejecutar este script"
    print_info "O clona tu repositorio: git clone <tu-repo> $APP_DIR"
    exit 1
fi

cd $APP_DIR

# Instalar dependencias
print_info "Instalando dependencias de Composer..."
sudo -u laravel composer install --no-dev --optimize-autoloader

print_info "Instalando dependencias de NPM..."
sudo -u laravel npm ci --production

print_info "Compilando assets..."
sudo -u laravel npm run build

print_success "Dependencias instaladas"

###############################################################################
# 11. Configurar .env
###############################################################################
print_info "Configurando archivo .env..."

if [ ! -f "$APP_DIR/.env" ]; then
    cp $APP_DIR/.env.example $APP_DIR/.env
fi

# Generar APP_KEY
sudo -u laravel php artisan key:generate --force

# Generar credenciales Reverb
REVERB_APP_ID=$(openssl rand -hex 3)
REVERB_APP_KEY=$(openssl rand -hex 12)
REVERB_APP_SECRET=$(openssl rand -hex 12)

# Configurar .env
cat > $APP_DIR/.env <<EOL
APP_NAME="JDT Gestión Incidencias"
APP_ENV=production
APP_KEY=$(grep APP_KEY $APP_DIR/.env | cut -d '=' -f2)
APP_DEBUG=false
APP_URL=https://$DOMAIN

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database

QUEUE_CONNECTION=database

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=$REVERB_APP_ID
REVERB_APP_KEY=$REVERB_APP_KEY
REVERB_APP_SECRET=$REVERB_APP_SECRET
REVERB_HOST="0.0.0.0"
REVERB_PORT=8080
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="\${REVERB_APP_KEY}"
VITE_REVERB_HOST="$DOMAIN"
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

DEEPSEEK_API_KEY=$DEEPSEEK_KEY
DEEPSEEK_API_URL=https://api.deepseek.com/v1
DEEPSEEK_MODEL=deepseek-chat
DEEPSEEK_MAX_TOKENS=1000
DEEPSEEK_TEMPERATURE=0.7
DEEPSEEK_TIMEOUT=30

MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@$DOMAIN
MAIL_FROM_NAME="\${APP_NAME}"
EOL

chown laravel:www-data $APP_DIR/.env
chmod 640 $APP_DIR/.env

print_success "Archivo .env configurado"

###############################################################################
# 12. Ejecutar migraciones
###############################################################################
print_info "Ejecutando migraciones y seeders..."
sudo -u laravel php artisan storage:link
sudo -u laravel php artisan migrate --seed --force
print_success "Base de datos inicializada"

###############################################################################
# 13. Optimizar Laravel
###############################################################################
print_info "Optimizando Laravel..."
sudo -u laravel php artisan config:cache
sudo -u laravel php artisan route:cache
sudo -u laravel php artisan view:cache
sudo -u laravel php artisan event:cache
print_success "Laravel optimizado"

###############################################################################
# 14. Configurar permisos
###############################################################################
print_info "Configurando permisos..."
chown -R laravel:www-data $APP_DIR
find $APP_DIR -type d -exec chmod 755 {} \;
find $APP_DIR -type f -exec chmod 644 {} \;
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache
print_success "Permisos configurados"

###############################################################################
# 15. Configurar PHP-FPM
###############################################################################
print_info "Optimizando PHP-FPM..."

cat > /etc/php/8.3/fpm/pool.d/www.conf <<'EOF'
[www]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 500
EOF

# Configurar php.ini
sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/8.3/fpm/php.ini
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 50M/' /etc/php/8.3/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini
sed -i 's/display_errors = .*/display_errors = Off/' /etc/php/8.3/fpm/php.ini
sed -i 's/expose_php = .*/expose_php = Off/' /etc/php/8.3/fpm/php.ini

# OPcache
sed -i 's/;opcache.enable=.*/opcache.enable=1/' /etc/php/8.3/fpm/php.ini
sed -i 's/;opcache.memory_consumption=.*/opcache.memory_consumption=256/' /etc/php/8.3/fpm/php.ini
sed -i 's/;opcache.validate_timestamps=.*/opcache.validate_timestamps=0/' /etc/php/8.3/fpm/php.ini

systemctl restart php8.3-fpm
print_success "PHP-FPM optimizado"

###############################################################################
# 16. Configurar Nginx
###############################################################################
print_info "Configurando Nginx..."

cat > /etc/nginx/sites-available/jdt-gestionincidencias <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;
    root $APP_DIR/public;
    index index.php index.html;

    access_log /var/log/nginx/jdt-access.log;
    error_log /var/log/nginx/jdt-error.log;

    client_max_body_size 50M;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    location /app {
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host \$host;
        proxy_pass http://127.0.0.1:8080;
        proxy_connect_timeout 7d;
        proxy_send_timeout 7d;
        proxy_read_timeout 7d;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

ln -sf /etc/nginx/sites-available/jdt-gestionincidencias /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

nginx -t
systemctl reload nginx

print_success "Nginx configurado"

###############################################################################
# 17. Configurar SSL
###############################################################################
print_info "Instalando y configurando SSL con Let's Encrypt..."

apt install -y certbot python3-certbot-nginx

certbot --nginx -d $DOMAIN --non-interactive --agree-tos --email $SSL_EMAIL --redirect

print_success "SSL configurado para $DOMAIN"

###############################################################################
# 18. Configurar Supervisor
###############################################################################
print_info "Configurando Supervisor..."

# Queue Workers
cat > /etc/supervisor/conf.d/jdt-queue-worker.conf <<EOF
[program:jdt-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $APP_DIR/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=laravel
numprocs=2
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/queue-worker.log
stopwaitsecs=3600
EOF

# Laravel Reverb
cat > /etc/supervisor/conf.d/jdt-reverb.conf <<EOF
[program:jdt-reverb]
process_name=%(program_name)s
command=php $APP_DIR/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=laravel
redirect_stderr=true
stdout_logfile=$APP_DIR/storage/logs/reverb.log
EOF

supervisorctl reread
supervisorctl update
supervisorctl start jdt-queue-worker:*
supervisorctl start jdt-reverb:*

print_success "Supervisor configurado"

###############################################################################
# 19. Configurar Cron
###############################################################################
print_info "Configurando tareas programadas..."

(crontab -u laravel -l 2>/dev/null; echo "* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -u laravel -

print_success "Cron configurado"

###############################################################################
# 20. Configurar Firewall
###############################################################################
print_info "Configurando firewall..."

ufw --force enable
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp

print_success "Firewall configurado"

###############################################################################
# 21. Configurar Fail2Ban
###############################################################################
print_info "Configurando Fail2Ban..."

cat > /etc/fail2ban/jail.local <<'EOF'
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
EOF

systemctl restart fail2ban

print_success "Fail2Ban configurado"

###############################################################################
# Resumen Final
###############################################################################
echo ""
print_success "==================================================================="
print_success "INSTALACIÓN COMPLETADA EXITOSAMENTE"
print_success "==================================================================="
echo ""
print_info "Acceso a la aplicación:"
echo "  URL: https://$DOMAIN"
echo "  Usuario: admin@gmail.com"
echo "  Contraseña: 123456"
echo ""
print_info "Base de datos:"
echo "  Nombre: $DB_NAME"
echo "  Usuario: $DB_USER"
echo ""
print_info "Servicios en ejecución:"
echo "  - Nginx: $(systemctl is-active nginx)"
echo "  - PHP-FPM: $(systemctl is-active php8.3-fpm)"
echo "  - PostgreSQL: $(systemctl is-active postgresql)"
echo "  - Supervisor: $(systemctl is-active supervisor)"
echo ""
print_info "Verificar procesos Supervisor:"
echo "  sudo supervisorctl status"
echo ""
print_info "Logs importantes:"
echo "  Laravel: tail -f $APP_DIR/storage/logs/laravel.log"
echo "  Nginx: tail -f /var/log/nginx/jdt-error.log"
echo "  Queue: tail -f $APP_DIR/storage/logs/queue-worker.log"
echo "  Reverb: tail -f $APP_DIR/storage/logs/reverb.log"
echo ""
print_success "¡Sistema listo para producción!"
echo ""
