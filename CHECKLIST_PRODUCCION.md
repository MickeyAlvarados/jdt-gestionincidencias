# Checklist de Despliegue en Producción

## Pre-Instalación

### Preparación del Servidor

- [ ] Servidor Ubuntu 22.04/24.04 LTS instalado y actualizado
- [ ] Acceso SSH configurado (con llave SSH, no contraseña)
- [ ] Usuario con privilegios sudo creado
- [ ] IP estática asignada al servidor
- [ ] Registro DNS configurado (dominio apuntando a la IP del servidor)
  ```bash
  # Verificar DNS
  nslookup incidencias.tudominio.com
  ```

### Requisitos de API y Servicios Externos

- [ ] API Key de DeepSeek obtenida de https://platform.deepseek.com/api_keys
- [ ] Email válido para certificados SSL (Let's Encrypt)
- [ ] Proveedor de email configurado (opcional, para notificaciones)
- [ ] Backup del código fuente disponible

### Información Necesaria

Tener a la mano:
- [ ] Nombre del dominio (ej: incidencias.tudominio.com)
- [ ] Nombre de base de datos deseado
- [ ] Usuario de base de datos deseado
- [ ] Contraseña segura para base de datos
- [ ] API Key de DeepSeek
- [ ] Email para SSL

---

## Instalación

### Opción 1: Script Automatizado (Recomendado)

```bash
# 1. Subir código al servidor
cd /var/www
sudo git clone <tu-repositorio> jdt-gestionincidencias
# O subir vía SFTP

# 2. Dar permisos de ejecución al script
cd jdt-gestionincidencias
sudo chmod +x install-production.sh

# 3. Ejecutar script
sudo ./install-production.sh
```

### Opción 2: Manual

- [ ] Seguir paso a paso el README_PRODUCTION.md

---

## Post-Instalación

### 1. Verificación de Servicios del Sistema

```bash
# Nginx
sudo systemctl status nginx
# Debe mostrar: active (running)

# PHP-FPM
sudo systemctl status php8.3-fpm
# Debe mostrar: active (running)

# PostgreSQL
sudo systemctl status postgresql
# Debe mostrar: active (running)

# Supervisor
sudo systemctl status supervisor
# Debe mostrar: active (running)
```

- [ ] Todos los servicios están activos y corriendo

### 2. Verificación de Procesos Supervisor

```bash
sudo supervisorctl status
```

Debe mostrar:
```
jdt-queue-worker:jdt-queue-worker_00   RUNNING
jdt-queue-worker:jdt-queue-worker_01   RUNNING
jdt-reverb:jdt-reverb                  RUNNING
```

- [ ] Queue workers corriendo (2 procesos)
- [ ] Laravel Reverb corriendo (1 proceso)

### 3. Verificación de Base de Datos

```bash
# Conectar a la base de datos
sudo -u postgres psql -d jdt_gestionincidencias

# Dentro de psql:
\dt  # Ver tablas
SELECT COUNT(*) FROM users;  # Debe haber al menos 2 usuarios (admin e IA)
SELECT COUNT(*) FROM roles;  # Debe haber 4 roles
\q   # Salir
```

- [ ] Base de datos creada
- [ ] Tablas migradas correctamente
- [ ] Datos iniciales cargados (seeders)
- [ ] Usuario admin existe
- [ ] Usuario IA (ia@support.local) existe

### 4. Verificación de Permisos

```bash
# Verificar propietario
ls -la /var/www/jdt-gestionincidencias

# Verificar storage
ls -la /var/www/jdt-gestionincidencias/storage

# Verificar bootstrap/cache
ls -la /var/www/jdt-gestionincidencias/bootstrap/cache
```

- [ ] Propietario: laravel:www-data
- [ ] storage/ tiene permisos 775
- [ ] bootstrap/cache/ tiene permisos 775
- [ ] .env tiene permisos 640 y propietario laravel

### 5. Verificación de Laravel

```bash
cd /var/www/jdt-gestionincidencias

# Ver información del sistema
sudo -u laravel php artisan about

# Verificar configuración
sudo -u laravel php artisan config:show database
sudo -u laravel php artisan config:show broadcasting
```

- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] Conexión a base de datos correcta
- [ ] Broadcasting configurado con reverb

### 6. Verificación de SSL/HTTPS

```bash
# Verificar certificado
sudo certbot certificates

# Probar conexión HTTPS
curl -I https://incidencias.tudominio.com
```

- [ ] Certificado SSL instalado
- [ ] Certificado válido
- [ ] HTTP redirige a HTTPS
- [ ] No hay errores de certificado

### 7. Verificación de Nginx

```bash
# Probar configuración
sudo nginx -t

# Ver logs
sudo tail -20 /var/log/nginx/jdt-error.log
```

- [ ] Sintaxis de configuración correcta
- [ ] No hay errores en los logs
- [ ] Server block configurado correctamente

### 8. Verificación de Conectividad Web

Abrir navegador y acceder a: https://incidencias.tudominio.com

- [ ] Página de login carga correctamente
- [ ] No hay errores de JavaScript en consola (F12)
- [ ] Assets (CSS, JS) cargan correctamente
- [ ] Imágenes se muestran correctamente

### 9. Verificación de Autenticación

Login con credenciales por defecto:
- Email: admin@gmail.com
- Password: 123456

- [ ] Login exitoso
- [ ] Dashboard carga correctamente
- [ ] Menú de navegación funciona
- [ ] No hay errores en consola

### 10. Verificación del Chat con IA

Ir a: https://incidencias.tudominio.com/chat

Abrir la consola del navegador (F12) antes de enviar mensajes.

#### Paso 1: Verificar Conexión WebSocket

En la pestaña Console:
- [ ] No hay errores de conexión WebSocket
- [ ] Se muestra: "Connected to WebSocket" o similar

En la pestaña Network > WS (WebSocket):
- [ ] Hay una conexión activa a ws:// o wss://
- [ ] Estado: 101 Switching Protocols

#### Paso 2: Enviar Mensaje de Prueba

Enviar: "Mi impresora no imprime, ¿qué puedo hacer?"

- [ ] Mensaje se envía correctamente
- [ ] Indicador de "Escribiendo..." aparece
- [ ] Respuesta de la IA llega en tiempo real (5-10 segundos)
- [ ] Respuesta es coherente y relevante
- [ ] No hay errores en consola

#### Paso 3: Verificar Logs Backend

```bash
# Ver logs de Laravel en tiempo real
sudo tail -f /var/www/jdt-gestionincidencias/storage/logs/laravel.log

# Ver logs de Queue Worker
sudo tail -f /var/www/jdt-gestionincidencias/storage/logs/queue-worker.log

# Ver logs de Reverb
sudo tail -f /var/www/jdt-gestionincidencias/storage/logs/reverb.log
```

- [ ] Job ProcessChatMessage se ejecuta
- [ ] No hay errores de API de DeepSeek
- [ ] Mensaje se transmite vía Reverb
- [ ] No hay excepciones no manejadas

#### Paso 4: Probar Escalamiento

Enviar: "No pude resolver mi problema, necesito ayuda técnica"

- [ ] Sistema crea una incidencia automáticamente
- [ ] Incidencia se asigna a un técnico
- [ ] Usuario recibe confirmación

### 11. Verificación de Jobs y Colas

```bash
# Ver trabajos en cola
cd /var/www/jdt-gestionincidencias
sudo -u laravel php artisan queue:work --once

# Ver trabajos fallidos
sudo -u laravel php artisan queue:failed
```

- [ ] Queue procesa trabajos correctamente
- [ ] No hay trabajos fallidos
- [ ] Tiempo de procesamiento es razonable (<30s)

### 12. Verificación de Cron

```bash
# Ver tareas programadas
sudo crontab -u laravel -l

# Verificar ejecución (esperar 1 minuto)
sudo tail -f /var/www/jdt-gestionincidencias/storage/logs/laravel.log
```

- [ ] Cron configurado para schedule:run
- [ ] Schedule se ejecuta cada minuto

### 13. Verificación de Firewall

```bash
sudo ufw status
```

- [ ] Firewall activo
- [ ] Puerto 22 (SSH) permitido
- [ ] Puerto 80 (HTTP) permitido
- [ ] Puerto 443 (HTTPS) permitido
- [ ] Puerto 8080 NO expuesto públicamente (solo localhost)

### 14. Verificación de Seguridad

```bash
# Verificar Fail2Ban
sudo fail2ban-client status

# Verificar permisos de .env
ls -la /var/www/jdt-gestionincidencias/.env
```

- [ ] Fail2Ban activo y monitoreando
- [ ] .env no es legible públicamente (640)
- [ ] APP_DEBUG=false en .env
- [ ] SSH root login deshabilitado
- [ ] SSH password authentication deshabilitado

### 15. Verificación de Backups

```bash
# Verificar script de backup
ls -la /usr/local/bin/backup-jdt-db.sh

# Ejecutar backup manual
sudo /usr/local/bin/backup-jdt-db.sh

# Verificar backup creado
ls -lh /var/backups/jdt/
```

- [ ] Script de backup existe
- [ ] Script es ejecutable
- [ ] Backup se crea correctamente
- [ ] Cron de backup configurado (2:00 AM diario)

### 16. Test de Rendimiento Básico

```bash
# Test de carga simple
ab -n 100 -c 10 https://incidencias.tudominio.com/
```

- [ ] Página responde correctamente bajo carga
- [ ] Tiempo de respuesta promedio < 500ms
- [ ] No hay errores 500

### 17. Verificación de Logs

```bash
# Ver todos los logs relevantes
sudo tail -50 /var/log/nginx/jdt-error.log
sudo tail -50 /var/log/nginx/jdt-access.log
sudo tail -50 /var/www/jdt-gestionincidencias/storage/logs/laravel.log
sudo tail -50 /var/log/postgresql/postgresql-17-main.log
sudo tail -50 /var/log/syslog
```

- [ ] No hay errores críticos en ningún log
- [ ] Accesos se están registrando correctamente
- [ ] No hay warnings preocupantes

---

## Checklist de Seguridad

### Configuración de Servidor

- [ ] Firewall UFW activo y configurado
- [ ] Fail2Ban instalado y activo
- [ ] SSH configurado con llaves (no contraseñas)
- [ ] Root login SSH deshabilitado
- [ ] Usuario sudo sin privilegios innecesarios

### Configuración de Aplicación

- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] Contraseñas de .env son seguras (>16 caracteres)
- [ ] .env tiene permisos restrictivos (640)
- [ ] API Keys no están en código fuente
- [ ] Logs no exponen información sensible

### Base de Datos

- [ ] Usuario de BD tiene solo los permisos necesarios
- [ ] Contraseña de BD es fuerte
- [ ] PostgreSQL no está expuesto públicamente (solo localhost)
- [ ] Backups automáticos configurados

### SSL/HTTPS

- [ ] Certificado SSL válido instalado
- [ ] HTTP redirige a HTTPS
- [ ] HSTS headers configurados
- [ ] Calificación SSL A o A+ (verificar en ssllabs.com)

---

## Checklist de Rendimiento

### PHP

- [ ] OPcache habilitado
- [ ] memory_limit apropiado (512M)
- [ ] PHP-FPM optimizado (pm.max_children configurado)

### Laravel

- [ ] Config cacheada (php artisan config:cache)
- [ ] Routes cacheadas (php artisan route:cache)
- [ ] Views cacheadas (php artisan view:cache)
- [ ] Events cacheados (php artisan event:cache)

### Nginx

- [ ] Gzip compression habilitada
- [ ] Cache de archivos estáticos configurado
- [ ] Client max body size apropiado (50M)

### PostgreSQL

- [ ] shared_buffers configurado
- [ ] effective_cache_size configurado
- [ ] Índices creados en tablas grandes

---

## Tareas Post-Despliegue

### Inmediatas

- [ ] Cambiar contraseña del usuario admin
- [ ] Cambiar contraseña de la base de datos si se usó una débil
- [ ] Configurar proveedor de email real (si se necesita)
- [ ] Configurar monitoreo (opcional: New Relic, Datadog, etc.)
- [ ] Documentar credenciales en gestor de contraseñas seguro

### Primeras 24 Horas

- [ ] Monitorear logs de errores
- [ ] Verificar que backups se ejecutan correctamente
- [ ] Probar todos los módulos principales
- [ ] Entrenar usuarios finales
- [ ] Configurar alertas de monitoreo

### Primera Semana

- [ ] Revisar rendimiento y optimizar si es necesario
- [ ] Ajustar configuración de PHP-FPM según carga
- [ ] Verificar uso de disco y planificar rotación de logs
- [ ] Realizar pruebas de carga más exhaustivas
- [ ] Documentar procedimientos de mantenimiento

---

## Comandos Útiles para Debugging

```bash
# Ver estado de todos los servicios
sudo systemctl status nginx php8.3-fpm postgresql supervisor

# Ver procesos de Supervisor
sudo supervisorctl status

# Ver logs en tiempo real
sudo tail -f /var/www/jdt-gestionincidencias/storage/logs/laravel.log
sudo tail -f /var/log/nginx/jdt-error.log

# Reiniciar servicios
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm
sudo supervisorctl restart jdt-queue-worker:*
sudo supervisorctl restart jdt-reverb:*

# Limpiar cachés de Laravel
cd /var/www/jdt-gestionincidencias
sudo -u laravel php artisan cache:clear
sudo -u laravel php artisan config:clear
sudo -u laravel php artisan route:clear
sudo -u laravel php artisan view:clear

# Verificar conectividad de base de datos
sudo -u laravel php artisan tinker
>>> DB::connection()->getPdo();

# Ver trabajos fallidos en la cola
sudo -u laravel php artisan queue:failed

# Reintentar trabajos fallidos
sudo -u laravel php artisan queue:retry all
```

---

## Contacto de Soporte

Si encuentras problemas durante el despliegue:

1. Revisa los logs en `/var/www/jdt-gestionincidencias/storage/logs/`
2. Verifica el estado de los servicios con `systemctl`
3. Consulta la sección de Troubleshooting en README_PRODUCTION.md
4. Contacta al equipo de desarrollo con capturas de los errores

---

**Sistema verificado y listo para producción ✓**

Fecha de verificación: ___________
Verificado por: ___________
