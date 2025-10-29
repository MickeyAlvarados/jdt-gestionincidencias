# Guía de Mantenimiento en Producción

## Tareas de Mantenimiento Rutinario

### Diarias

#### 1. Revisar Logs de Errores

```bash
# Últimos errores de Laravel
sudo tail -100 /var/www/jdt-gestionincidencias/storage/logs/laravel.log | grep ERROR

# Últimos errores de Nginx
sudo tail -100 /var/log/nginx/jdt-error.log

# Últimos errores de PHP-FPM
sudo tail -100 /var/log/php8.3-fpm.log
```

#### 2. Verificar Estado de Servicios

```bash
# Script de verificación rápida
sudo systemctl status nginx php8.3-fpm postgresql supervisor
sudo supervisorctl status
```

#### 3. Monitorear Uso de Recursos

```bash
# Uso de CPU y memoria
htop

# Uso de disco
df -h

# Espacio en storage/logs
du -sh /var/www/jdt-gestionincidencias/storage/logs/*

# Procesos PHP
ps aux | grep php
```

#### 4. Verificar Queue Workers

```bash
# Ver trabajos fallidos
cd /var/www/jdt-gestionincidencias
sudo -u laravel php artisan queue:failed

# Si hay trabajos fallidos, investigar y reintentar
sudo -u laravel php artisan queue:retry all
```

---

### Semanales

#### 1. Limpiar Logs Antiguos

```bash
# Laravel logs mayores a 7 días
find /var/www/jdt-gestionincidencias/storage/logs -name "*.log" -mtime +7 -delete

# Limpiar logs del sistema
sudo journalctl --vacuum-time=7d
```

#### 2. Optimizar Base de Datos

```bash
# Conectar a PostgreSQL
sudo -u postgres psql -d jdt_gestionincidencias

-- Dentro de psql:
VACUUM ANALYZE;
REINDEX DATABASE jdt_gestionincidencias;
\q
```

#### 3. Verificar Backups

```bash
# Listar backups recientes
ls -lh /var/backups/jdt/ | tail -10

# Verificar que el backup de hoy existe
ls -lh /var/backups/jdt/db_backup_$(date +%Y%m%d)*.sql.gz
```

#### 4. Actualizar Certificado SSL (si necesario)

```bash
# Verificar expiración
sudo certbot certificates

# Renovar si es necesario
sudo certbot renew
```

#### 5. Revisar Seguridad

```bash
# Ver intentos de acceso bloqueados por Fail2Ban
sudo fail2ban-client status nginx-limit-req
sudo fail2ban-client status sshd

# Revisar últimos logins SSH
last -20
```

---

### Mensuales

#### 1. Actualizar Sistema

```bash
# Actualizar paquetes
sudo apt update
sudo apt upgrade -y

# Reiniciar servicios si es necesario
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
sudo supervisorctl restart all
```

#### 2. Analizar Performance

```bash
# Consultas lentas en PostgreSQL
sudo -u postgres psql -d jdt_gestionincidencias -c "SELECT * FROM pg_stat_statements ORDER BY total_time DESC LIMIT 10;"

# Tiempo de respuesta de Nginx
sudo cat /var/log/nginx/jdt-access.log | awk '{print $NF}' | sort -n | tail -100
```

#### 3. Revisar Espacio en Disco

```bash
# Ver qué consume más espacio
sudo du -h --max-depth=1 /var/www/jdt-gestionincidencias | sort -hr

# Limpiar cache de Composer (si necesario)
sudo -u laravel composer clear-cache
```

#### 4. Test de Restauración de Backup

```bash
# Crear base de datos de prueba
sudo -u postgres psql <<EOF
CREATE DATABASE jdt_gestionincidencias_test;
EOF

# Restaurar último backup
LAST_BACKUP=$(ls -t /var/backups/jdt/db_backup_*.sql.gz | head -1)
gunzip -c $LAST_BACKUP | sudo -u postgres psql -d jdt_gestionincidencias_test

# Verificar datos
sudo -u postgres psql -d jdt_gestionincidencias_test -c "SELECT COUNT(*) FROM users;"

# Eliminar base de datos de prueba
sudo -u postgres psql -c "DROP DATABASE jdt_gestionincidencias_test;"
```

---

## Comandos de Troubleshooting

### Problema: Aplicación no responde (502 Bad Gateway)

```bash
# 1. Verificar PHP-FPM
sudo systemctl status php8.3-fpm

# 2. Ver logs de PHP-FPM
sudo tail -50 /var/log/php8.3-fpm.log

# 3. Verificar que el socket existe
ls -la /run/php/php8.3-fpm.sock

# 4. Reiniciar PHP-FPM
sudo systemctl restart php8.3-fpm

# 5. Verificar Nginx
sudo nginx -t
sudo systemctl reload nginx
```

### Problema: Chat no funciona (WebSocket no conecta)

```bash
# 1. Verificar que Reverb está corriendo
sudo supervisorctl status jdt-reverb

# 2. Ver logs de Reverb
sudo tail -100 /var/www/jdt-gestionincidencias/storage/logs/reverb.log

# 3. Verificar puerto 8080
sudo netstat -tulpn | grep 8080

# 4. Reiniciar Reverb
sudo supervisorctl restart jdt-reverb:*

# 5. Verificar configuración de Nginx para WebSocket
sudo nginx -T | grep -A 20 "location /app"

# 6. Probar conexión WebSocket manualmente
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" http://127.0.0.1:8080/app
```

### Problema: Mensajes de IA no llegan

```bash
# 1. Verificar Queue Workers
sudo supervisorctl status jdt-queue-worker

# 2. Ver logs de Queue
sudo tail -100 /var/www/jdt-gestionincidencias/storage/logs/queue-worker.log

# 3. Ver trabajos fallidos
cd /var/www/jdt-gestionincidencias
sudo -u laravel php artisan queue:failed

# 4. Ver tabla de jobs en la base de datos
sudo -u postgres psql -d jdt_gestionincidencias -c "SELECT * FROM jobs LIMIT 10;"

# 5. Procesar un job manualmente
sudo -u laravel php artisan queue:work --once

# 6. Verificar API Key de DeepSeek
grep DEEPSEEK_API_KEY /var/www/jdt-gestionincidencias/.env

# 7. Reiniciar Queue Workers
sudo supervisorctl restart jdt-queue-worker:*
```

### Problema: Error de base de datos

```bash
# 1. Verificar PostgreSQL
sudo systemctl status postgresql

# 2. Ver logs de PostgreSQL
sudo tail -100 /var/log/postgresql/postgresql-17-main.log

# 3. Probar conexión
sudo -u postgres psql -d jdt_gestionincidencias -c "SELECT 1;"

# 4. Verificar configuración en .env
grep DB_ /var/www/jdt-gestionincidencias/.env

# 5. Probar conexión desde Laravel
cd /var/www/jdt-gestionincidencias
sudo -u laravel php artisan tinker
>>> DB::connection()->getPdo();

# 6. Reiniciar PostgreSQL
sudo systemctl restart postgresql
```

### Problema: Errores de permisos

```bash
# 1. Verificar propietario
ls -la /var/www/jdt-gestionincidencias

# 2. Corregir permisos
sudo chown -R laravel:www-data /var/www/jdt-gestionincidencias
sudo find /var/www/jdt-gestionincidencias -type d -exec chmod 755 {} \;
sudo find /var/www/jdt-gestionincidencias -type f -exec chmod 644 {} \;
sudo chmod -R 775 /var/www/jdt-gestionincidencias/storage
sudo chmod -R 775 /var/www/jdt-gestionincidencias/bootstrap/cache

# 3. Verificar permisos de .env
sudo chmod 640 /var/www/jdt-gestionincidencias/.env
sudo chown laravel:www-data /var/www/jdt-gestionincidencias/.env
```

### Problema: Alto uso de CPU

```bash
# 1. Identificar procesos
top -o %CPU

# 2. Si PHP-FPM consume mucho
sudo ps aux | grep php-fpm | wc -l

# 3. Ver configuración de PHP-FPM
cat /etc/php/8.3/fpm/pool.d/www.conf | grep -E "pm\.|max_children"

# 4. Ajustar si es necesario
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
# Reducir pm.max_children si hay problemas de memoria

# 5. Reiniciar PHP-FPM
sudo systemctl restart php8.3-fpm
```

### Problema: Alto uso de memoria

```bash
# 1. Ver uso de memoria
free -h

# 2. Procesos que más consumen
ps aux --sort=-%mem | head -10

# 3. Limpiar caché del sistema
sudo sync
sudo sh -c 'echo 3 > /proc/sys/vm/drop_caches'

# 4. Limpiar cachés de Laravel
cd /var/www/jdt-gestionincidencias
sudo -u laravel php artisan cache:clear
sudo -u laravel php artisan view:clear
```

### Problema: Disco lleno

```bash
# 1. Ver uso de disco
df -h

# 2. Encontrar archivos grandes
sudo du -ah /var/www/jdt-gestionincidencias | sort -rh | head -20

# 3. Limpiar logs antiguos
sudo find /var/www/jdt-gestionincidencias/storage/logs -name "*.log" -mtime +7 -delete
sudo journalctl --vacuum-time=7d

# 4. Limpiar backups antiguos
sudo find /var/backups/jdt -type f -mtime +30 -delete

# 5. Limpiar caché de apt
sudo apt clean
```

---

## Scripts Útiles de Mantenimiento

### Script de Verificación de Salud

Crear `/usr/local/bin/check-jdt-health.sh`:

```bash
#!/bin/bash

echo "=== Verificación de Salud - JDT GestiónIncidencias ==="
echo ""

# Servicios
echo "## Servicios"
systemctl is-active --quiet nginx && echo "✓ Nginx: OK" || echo "✗ Nginx: FAIL"
systemctl is-active --quiet php8.3-fpm && echo "✓ PHP-FPM: OK" || echo "✗ PHP-FPM: FAIL"
systemctl is-active --quiet postgresql && echo "✓ PostgreSQL: OK" || echo "✗ PostgreSQL: FAIL"
systemctl is-active --quiet supervisor && echo "✓ Supervisor: OK" || echo "✗ Supervisor: FAIL"
echo ""

# Supervisor
echo "## Procesos Supervisor"
supervisorctl status | grep -q "RUNNING" && echo "✓ Processes: OK" || echo "✗ Processes: FAIL"
supervisorctl status
echo ""

# Disco
echo "## Espacio en Disco"
df -h / | tail -1 | awk '{print "Uso: " $5 " (" $3 " de " $2 ")"}'
echo ""

# Memoria
echo "## Memoria"
free -h | grep Mem | awk '{print "Uso: " $3 " de " $2 " (" int($3/$2*100) "%)"}'
echo ""

# CPU
echo "## CPU"
uptime
echo ""

# Base de datos
echo "## Base de Datos"
sudo -u postgres psql -d jdt_gestionincidencias -c "SELECT COUNT(*) as total_users FROM users;" -t
sudo -u postgres psql -d jdt_gestionincidencias -c "SELECT COUNT(*) as total_chats FROM chat;" -t
echo ""

# Logs recientes
echo "## Errores Recientes"
ERROR_COUNT=$(tail -1000 /var/www/jdt-gestionincidencias/storage/logs/laravel.log | grep -c ERROR)
echo "Errores Laravel (últimas 1000 líneas): $ERROR_COUNT"
echo ""

# SSL
echo "## Certificado SSL"
certbot certificates 2>/dev/null | grep "Expiry Date" | head -1
echo ""

# Backup
echo "## Último Backup"
ls -lh /var/backups/jdt/db_backup_*.sql.gz | tail -1
echo ""

echo "=== Fin de Verificación ==="
```

Hacer ejecutable:

```bash
sudo chmod +x /usr/local/bin/check-jdt-health.sh
```

Usar:

```bash
sudo /usr/local/bin/check-jdt-health.sh
```

### Script de Limpieza de Logs

Crear `/usr/local/bin/clean-jdt-logs.sh`:

```bash
#!/bin/bash

APP_DIR="/var/www/jdt-gestionincidencias"
DAYS_TO_KEEP=7

echo "Limpiando logs antiguos (más de $DAYS_TO_KEEP días)..."

# Laravel logs
find $APP_DIR/storage/logs -name "*.log" -mtime +$DAYS_TO_KEEP -delete
echo "✓ Laravel logs limpiados"

# Journal
journalctl --vacuum-time=${DAYS_TO_KEEP}d
echo "✓ System journal limpiado"

# Nginx logs
find /var/log/nginx -name "*.log.*" -mtime +$DAYS_TO_KEEP -delete
echo "✓ Nginx logs limpiados"

# PostgreSQL logs
find /var/log/postgresql -name "*.log.*" -mtime +$DAYS_TO_KEEP -delete
echo "✓ PostgreSQL logs limpiados"

echo "Limpieza completada"
```

Hacer ejecutable y programar:

```bash
sudo chmod +x /usr/local/bin/clean-jdt-logs.sh

# Agregar a cron (ejecutar cada domingo a las 3 AM)
sudo crontab -e
# Agregar:
# 0 3 * * 0 /usr/local/bin/clean-jdt-logs.sh
```

### Script de Reinicio Completo

Crear `/usr/local/bin/restart-jdt-app.sh`:

```bash
#!/bin/bash

echo "Reiniciando JDT GestiónIncidencias..."

# Modo mantenimiento
cd /var/www/jdt-gestionincidencias
sudo -u laravel php artisan down
echo "✓ Modo mantenimiento activado"

# Reiniciar servicios
sudo systemctl restart php8.3-fpm
echo "✓ PHP-FPM reiniciado"

sudo systemctl reload nginx
echo "✓ Nginx recargado"

sudo supervisorctl restart jdt-queue-worker:*
echo "✓ Queue Workers reiniciados"

sudo supervisorctl restart jdt-reverb:*
echo "✓ Reverb reiniciado"

# Limpiar cachés
sudo -u laravel php artisan cache:clear
sudo -u laravel php artisan config:cache
sudo -u laravel php artisan route:cache
sudo -u laravel php artisan view:cache
echo "✓ Cachés limpiados y reconstruidos"

# Salir de mantenimiento
sudo -u laravel php artisan up
echo "✓ Modo mantenimiento desactivado"

echo "Reinicio completado"
```

Hacer ejecutable:

```bash
sudo chmod +x /usr/local/bin/restart-jdt-app.sh
```

---

## Monitoreo con Alertas (Opcional)

### Script de Monitoreo con Email

Instalar mailutils:

```bash
sudo apt install -y mailutils
```

Crear `/usr/local/bin/monitor-jdt-app.sh`:

```bash
#!/bin/bash

ADMIN_EMAIL="tu_email@gmail.com"
APP_NAME="JDT GestiónIncidencias"

# Verificar servicios críticos
SERVICES=("nginx" "php8.3-fpm" "postgresql" "supervisor")

for SERVICE in "${SERVICES[@]}"; do
    if ! systemctl is-active --quiet $SERVICE; then
        echo "$APP_NAME ALERT: $SERVICE is down!" | mail -s "[$APP_NAME] Service Down: $SERVICE" $ADMIN_EMAIL
        systemctl start $SERVICE
    fi
done

# Verificar Supervisor
if ! supervisorctl status | grep -q "RUNNING"; then
    echo "$APP_NAME ALERT: Some Supervisor processes are not running!" | mail -s "[$APP_NAME] Supervisor Issue" $ADMIN_EMAIL
    supervisorctl restart all
fi

# Verificar disco
DISK_USAGE=$(df -h / | tail -1 | awk '{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 85 ]; then
    echo "$APP_NAME ALERT: Disk usage is at ${DISK_USAGE}%" | mail -s "[$APP_NAME] High Disk Usage" $ADMIN_EMAIL
fi

# Verificar errores recientes
ERROR_COUNT=$(tail -100 /var/www/jdt-gestionincidencias/storage/logs/laravel.log | grep -c ERROR)
if [ $ERROR_COUNT -gt 10 ]; then
    echo "$APP_NAME ALERT: $ERROR_COUNT errors found in last 100 log lines" | mail -s "[$APP_NAME] High Error Count" $ADMIN_EMAIL
fi
```

Hacer ejecutable y programar:

```bash
sudo chmod +x /usr/local/bin/monitor-jdt-app.sh

# Ejecutar cada 5 minutos
sudo crontab -e
# Agregar:
# */5 * * * * /usr/local/bin/monitor-jdt-app.sh
```

---

## Comandos de Emergencia

### Aplicación no responde en absoluto

```bash
# Reinicio completo de emergencia
sudo systemctl restart nginx php8.3-fpm postgresql
sudo supervisorctl restart all
```

### Base de datos corrompida

```bash
# Restaurar último backup
sudo -u laravel php artisan down

LAST_BACKUP=$(ls -t /var/backups/jdt/db_backup_*.sql.gz | head -1)
sudo -u postgres psql -d jdt_gestionincidencias -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;"
gunzip -c $LAST_BACKUP | sudo -u postgres psql -d jdt_gestionincidencias

sudo -u laravel php artisan migrate --force
sudo -u laravel php artisan up
```

### Servidor comprometido (sospecha de hack)

```bash
# 1. Modo mantenimiento inmediato
cd /var/www/jdt-gestionincidencias
sudo -u laravel php artisan down

# 2. Desconectar de internet (si es posible)
sudo ufw deny out

# 3. Revisar últimos logins
last -50
lastlog

# 4. Revisar procesos sospechosos
ps aux | grep -v "^\[" | grep -v "kworker"

# 5. Revisar conexiones activas
sudo netstat -tulpn

# 6. Cambiar todas las contraseñas
# - Base de datos
# - .env
# - Usuarios del sistema
# - DeepSeek API Key

# 7. Contactar a seguridad/soporte
```

---

## Registro de Mantenimiento

Es recomendable llevar un log de mantenimiento:

```bash
# Crear archivo de log
sudo touch /var/log/jdt-maintenance.log

# Ejemplo de entrada manual
echo "$(date '+%Y-%m-%d %H:%M:%S') - [TU_NOMBRE] - Reinicio de servicios por alto uso de memoria" | sudo tee -a /var/log/jdt-maintenance.log
```

---

## Recursos Adicionales

- Laravel Logs: `/var/www/jdt-gestionincidencias/storage/logs/`
- Nginx Logs: `/var/log/nginx/`
- PostgreSQL Logs: `/var/log/postgresql/`
- System Logs: `sudo journalctl -u <service>`

**Mantén tu sistema saludable y actualizado!**
