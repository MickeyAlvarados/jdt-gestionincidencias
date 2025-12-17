# Pasos para Aplicar la Corrección del Error 419 en Producción

## 🔴 Problema Identificado

**Error principal:** `SESSION_DOMAIN=` está vacío en el archivo `.env` de producción (línea 37).

**Errores secundarios:**
- Línea 1: `PP_NAME=Laravel` (falta la "A")
- Línea 95: `REVERB_PORT=8080` (debería ser 80)
- Configuración de Nginx incompleta (faltan headers CSRF)

---

## ✅ Solución Paso a Paso

### **1. Conectarse al Servidor**

```bash
ssh usuario@tu-servidor
```

### **2. Hacer Backup de los Archivos Actuales**

```bash
# Ir al directorio del proyecto
cd /var/www/jungledev/jdt/jdt-gestionincidencias

# Crear backup del .env actual
cp .env .env.backup-$(date +%Y%m%d-%H%M%S)

# Backup de la configuración de Nginx
sudo cp /etc/nginx/sites-available/gestionincidentes.jungledevperu.com /etc/nginx/sites-available/gestionincidentes.jungledevperu.com.backup-$(date +%Y%m%d-%H%M%S)
```

### **3. Actualizar el Archivo .env**

```bash
# Editar el archivo .env
nano .env
```

**Cambios a realizar:**

```bash
# LÍNEA 1: Corregir APP_NAME
# DE:  PP_NAME=Laravel
# A:   APP_NAME=Laravel

# LÍNEA 37: Agregar el dominio de sesión (CRÍTICO)
# DE:  SESSION_DOMAIN=
# A:   SESSION_DOMAIN=gestionincidentes.jungledevperu.com

# LÍNEA 95: Corregir el puerto de Reverb
# DE:  REVERB_PORT=8080
# A:   REVERB_PORT=80

# Además, asegúrate de agregar estas líneas si no existen:
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

**O puedes reemplazar el archivo completo:**

```bash
# Subir el archivo .env-prod-CORREGIDO desde tu máquina local
# Luego en el servidor:
cp /ruta/donde/subiste/.env-prod-CORREGIDO .env
```

### **4. Actualizar la Configuración de Nginx**

```bash
# Editar la configuración de Nginx
sudo nano /etc/nginx/sites-available/gestionincidentes.jungledevperu.com
```

**Cambios críticos en la sección PHP (alrededor de la línea 24-35):**

Reemplazar:
```nginx
location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

Por:
```nginx
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;

    # Headers críticos para CSRF y sesiones
    fastcgi_param HTTP_X_FORWARDED_FOR $proxy_add_x_forwarded_for;
    fastcgi_param HTTP_X_REAL_IP $remote_addr;
    fastcgi_param HTTP_X_FORWARDED_PROTO $scheme;
    fastcgi_param HTTP_HOST $host;

    fastcgi_read_timeout 300;
    fastcgi_send_timeout 300;
}
```

**O puedes reemplazar el archivo completo:**
```bash
# Subir el archivo nginx-CORREGIDO.conf desde tu máquina local
# Luego en el servidor:
sudo cp /ruta/donde/subiste/nginx-CORREGIDO.conf /etc/nginx/sites-available/gestionincidentes.jungledevperu.com
```

### **5. Verificar Configuración de Nginx**

```bash
# Probar la configuración de Nginx
sudo nginx -t

sudo ln -s /etc/nginx/sites-available/gestionincidentes.jungledevperu.com /etc/nginx/sites-enabled/ 
```

**Salida esperada:**
```
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

### **6. Limpiar Cachés de Laravel**

```bash
cd /var/www/jungledev/jdt/jdt-gestionincidencias

# Limpiar TODOS los cachés
sudo php artisan config:clear
sudo php artisan cache:clear
sudo php artisan route:clear
sudo php artisan view:clear

# Regenerar caché de configuración
sudo php artisan config:cache
```

### **7. Verificar Tabla de Sesiones**

```bash
# Verificar que existe la tabla sessions
php artisan migrate:status | grep sessions

# Si no existe, crearla
php artisan session:table
php artisan migrate --force
```

### **8. Corregir Permisos**

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### **9. Reiniciar Servicios**

```bash
# Reiniciar PHP-FPM (limpia el opcache)
sudo systemctl restart php8.3-fpm

# Recargar Nginx (aplica la nueva configuración)
sudo systemctl reload nginx

# Reiniciar Reverb
pkill -f "artisan reverb"
nohup php artisan reverb:start --host=0.0.0.0 --port=8080 > storage/logs/reverb.log 2>&1 &

# Reiniciar Queue Worker
pkill -f "artisan queue:work"
nohup php artisan queue:work --sleep=3 --tries=3 > storage/logs/queue-worker.log 2>&1 &
```

### **10. Verificar que los Servicios Están Corriendo**

```bash
# Verificar Reverb
ps aux | grep "artisan reverb"

# Verificar Queue Worker
ps aux | grep "artisan queue:work"

# Verificar Puerto 8080 (Reverb)
sudo netstat -tlnp | grep 8080

# Debería mostrar algo como:
# tcp  0  0  0.0.0.0:8080  0.0.0.0:*  LISTEN  12345/php
```

---

## 🧪 Pruebas

### **1. Verificar Variables de Entorno**

```bash
cd /var/www/jungledev/jdt/jdt-gestionincidencias

# Ver las variables críticas
echo "SESSION_DOMAIN: $(grep SESSION_DOMAIN .env | cut -d '=' -f2)"
echo "REVERB_PORT: $(grep "^REVERB_PORT" .env | cut -d '=' -f2)"
echo "VITE_REVERB_PORT: $(grep VITE_REVERB_PORT .env | cut -d '=' -f2)"
echo "APP_NAME: $(grep "^APP_NAME" .env | cut -d '=' -f2)"
```

**Salida esperada:**
```
SESSION_DOMAIN: gestionincidentes.jungledevperu.com
REVERB_PORT: 80
VITE_REVERB_PORT: 80
APP_NAME: Laravel
```

### **2. Probar en el Navegador**

1. Abre **Chrome o Firefox en modo incógnito** (Ctrl+Shift+N)
2. Abre las **DevTools** (F12)
3. Ve a la pestaña **Network**
4. Navega a: `http://gestionincidentes.jungledevperu.com`
5. Inicia sesión
6. Ve al Chat
7. Observa la petición `POST /chat/crear-sesion`:
   - ✅ **Status 200**: Problema resuelto
   - ❌ **Status 419**: Revisar logs

### **3. Verificar Logs (si falla)**

```bash
# Ver logs de Laravel
tail -f storage/logs/laravel.log

# Ver logs de Nginx
sudo tail -f /var/log/nginx/error.log

# Ver logs de Reverb
tail -f storage/logs/reverb.log
```

---

## 📋 Checklist Final

Verifica que cada punto esté correcto:

- [ ] `SESSION_DOMAIN=gestionincidentes.jungledevperu.com` en .env
- [ ] `APP_NAME=Laravel` (con "A" al inicio) en .env
- [ ] `REVERB_PORT=80` en .env
- [ ] `VITE_REVERB_PORT=80` en .env
- [ ] Headers CSRF agregados en configuración de Nginx
- [ ] `nginx -t` pasa sin errores
- [ ] Cachés limpiados con `php artisan config:clear` y `php artisan config:cache`
- [ ] PHP-FPM reiniciado
- [ ] Nginx recargado
- [ ] Reverb corriendo en puerto 8080
- [ ] Queue Worker corriendo
- [ ] Prueba en navegador modo incógnito: ✅ Sin error 419

---

## 🆘 Si Aún Falla

1. **Ver headers de la petición en el navegador:**
   - F12 > Network > Clic en la petición fallida
   - Pestaña "Headers" > Buscar "Cookie"
   - Verificar que aparece la cookie de sesión (ej: `laravel_session=...`)

2. **Verificar que la cookie se está creando:**
   ```bash
   # En tu navegador, después de login:
   # F12 > Application > Cookies > http://gestionincidentes.jungledevperu.com
   # Debería aparecer: laravel_session
   ```

3. **Verificar la base de datos:**
   ```bash
   # Conectar a PostgreSQL
   sudo -u postgres psql -d jdt-gestionincidencias -c "SELECT COUNT(*) FROM sessions;"

   # Debería mostrar sesiones activas
   ```

4. **Contactar soporte:**
   - Envía los logs: `storage/logs/laravel.log`
   - Captura de pantalla del error en F12 > Network
   - Output de: `php artisan config:show session`

---

**¡Listo! Tu aplicación debería funcionar sin el error 419.**
