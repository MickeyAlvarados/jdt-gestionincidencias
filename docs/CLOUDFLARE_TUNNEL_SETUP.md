# Configuración de Cloudflare Tunnel para JDT-GestiónIncidencias

## Problema Resuelto

Este documento explica cómo resolver el error de **Mixed Content** que ocurre al exponer tu aplicación local mediante Cloudflare Tunnel:

```
'https://tu-dominio.trycloudflare.com/login' was loaded over HTTPS, but requested
an insecure XMLHttpRequest endpoint 'http://tu-dominio.trycloudflare.com/login'.
This request has been blocked; the content must be served over HTTPS.
```

## ¿Qué Causa Este Error?

Cuando usas Cloudflare Tunnel, tu aplicación se expone públicamente vía **HTTPS**, pero Laravel sigue ejecutándose localmente en **HTTP**. El navegador bloquea peticiones HTTP desde páginas HTTPS por seguridad (mixed content).

## Solución Implementada

### 1. Configuración de TrustProxies (bootstrap/app.php)

Se configuró Laravel para confiar en los headers enviados por Cloudflare:

```php
$middleware->trustProxies(
    at: '*',
    headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
            \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
);
```

### 2. Forzar HTTPS (app/Providers/AppServiceProvider.php)

Se configuró Laravel para generar todas las URLs con HTTPS cuando detecta un proxy:

```php
public function boot(): void
{
    if ($this->app->environment('production') || request()->header('X-Forwarded-Proto') === 'https') {
        URL::forceScheme('https');
    }
}
```

## Configuración de Variables de Entorno (.env)

### Para Desarrollo Local (sin Cloudflare Tunnel)

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Reverb para WebSockets locales
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Para Desarrollo con Cloudflare Tunnel

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.trycloudflare.com

# Reverb para WebSockets con Cloudflare Tunnel
REVERB_HOST="tu-dominio.trycloudflare.com"
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**Importante**: Reemplaza `tu-dominio.trycloudflare.com` con tu URL real de Cloudflare Tunnel.

## Pasos para Exponer tu Aplicación con Cloudflare Tunnel

### Método 1: Script Automatizado (Recomendado)

Usa el script `start-dev-cloudflare.ps1` que inicia todos los servicios automáticamente, incluyendo Cloudflare Tunnel:

**Windows PowerShell:**
```powershell
.\start-dev-cloudflare.ps1
```

**Linux/Mac:**
```bash
chmod +x start-dev-cloudflare.sh
./start-dev-cloudflare.sh
```

El script hará lo siguiente:
1. Verificará la configuración de tu proyecto
2. Iniciará Queue Worker, Reverb y Laravel Server
3. Iniciará Cloudflare Tunnel automáticamente
4. Mostrará la URL pública generada
5. Te indicará los cambios que debes hacer en el `.env`

### Método 2: Manual

#### 1. Instalar Cloudflared (si no lo tienes)

**Windows:**
```powershell
# Descarga desde: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/install-and-setup/installation/
# O usa winget:
winget install --id Cloudflare.cloudflared
```

#### 2. Iniciar Servicios Base

**Opción A: Usar el script existente**
```powershell
.\start-dev.ps1
```

**Opción B: Iniciar manualmente (3 terminales)**
```powershell
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: Queue Worker
php artisan queue:work --tries=3

# Terminal 3: Reverb WebSocket
php artisan reverb:start
```

#### 3. Iniciar el Túnel (terminal adicional)

```powershell
# Túnel rápido (URL temporal)
cloudflared tunnel --url http://localhost:8000
```

### Siguiente Paso: Copiar la URL del Túnel

Cloudflared mostrará algo como:
```
+--------------------------------------------------------------------------------------------+
|  Your quick Tunnel has been created! Visit it at (it may take some time to be reachable):  |
|  https://occupation-belong-costa-jerry.trycloudflare.com                                   |
+--------------------------------------------------------------------------------------------+
```

## Configuración del .env

**Edita tu archivo `.env`** con la URL del túnel que obtuviste:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://occupation-belong-costa-jerry.trycloudflare.com

REVERB_HOST="occupation-belong-costa-jerry.trycloudflare.com"
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## Limpiar Cachés y Reconstruir Frontend

```powershell
# Limpiar cachés de Laravel
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Reconstruir assets de Vite (IMPORTANTE)
npm run build
```

## Configuración Avanzada: Cloudflare Tunnel para WebSockets

Para que los WebSockets funcionen correctamente, necesitas exponer también el puerto de Reverb (8080):

```powershell
# Opción 1: Usar un archivo de configuración
# Crea un archivo config.yml:
```

```yaml
tunnel: tu-tunnel-id
credentials-file: C:\Users\TuUsuario\.cloudflared\tu-tunnel-id.json

ingress:
  - hostname: tu-app.example.com
    service: http://localhost:8000
  - hostname: ws.tu-app.example.com
    service: http://localhost:8080
  - service: http_status:404
```

```powershell
# Luego ejecuta:
cloudflared tunnel run tu-tunnel-id
```

**Opción 2: Usar un túnel por puerto (más simple para desarrollo)**

```powershell
# Terminal 1: Túnel para la aplicación
cloudflared tunnel --url http://localhost:8000

# Terminal 2: Túnel para WebSockets
cloudflared tunnel --url http://localhost:8080
```

Luego actualiza el `.env` con las URLs correspondientes:
```env
REVERB_HOST="tu-dominio-ws.trycloudflare.com"
```

## Verificación

1. Visita tu URL de Cloudflare Tunnel: `https://tu-dominio.trycloudflare.com`
2. Abre las DevTools del navegador (F12)
3. Ve a la pestaña **Console** y verifica que NO haya errores de Mixed Content
4. Ve a la pestaña **Network** y verifica que todas las peticiones usen HTTPS

## Solución de Problemas

### El chat con IA no funciona

**Problema**: Los WebSockets no se conectan correctamente.

**Solución**:
1. Asegúrate de que `php artisan reverb:start` esté ejecutándose
2. Verifica que `REVERB_HOST` y `REVERB_SCHEME` estén correctamente configurados en `.env`
3. Si usas Cloudflare Tunnel, considera exponer el puerto 8080 por separado

### Las peticiones AJAX siguen usando HTTP

**Problema**: La aplicación sigue generando URLs en HTTP.

**Solución**:
1. Verifica que `APP_ENV=production` en `.env`
2. Limpia cachés: `php artisan config:clear`
3. Reconstruye frontend: `npm run build`
4. Reinicia el servidor Laravel

### Error 419 CSRF Token Mismatch

**Problema**: Los tokens CSRF no coinciden con HTTPS.

**Solución**:
1. Asegúrate de que `SESSION_SECURE_COOKIE=true` en `.env` para producción
2. Verifica que `APP_URL` use HTTPS
3. Limpia cookies del navegador

## Notas Importantes

1. **APP_ENV=production**: Es necesario para activar el forzado de HTTPS
2. **npm run build**: Debes reconstruir el frontend cada vez que cambies variables de entorno que empiecen con `VITE_`
3. **Túneles temporales**: Las URLs de `trycloudflare.com` son temporales y cambian cada vez que reinicias el túnel
4. **Túneles permanentes**: Para URLs permanentes, registra un túnel en el dashboard de Cloudflare
5. **WebSockets**: Los WebSockets pueden no funcionar correctamente a través de Cloudflare Tunnel sin configuración adicional

## Alternativas para WebSockets

Si tienes problemas con WebSockets a través de Cloudflare Tunnel, considera:

1. **Usar Pusher en lugar de Reverb**: Servicio cloud de WebSockets
2. **ngrok**: Alternativa a Cloudflare Tunnel con mejor soporte para WebSockets
3. **Exponer Reverb directamente**: Configurar firewall para permitir conexiones al puerto 8080 (solo para desarrollo)

## Referencias

- [Cloudflare Tunnel Documentation](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/)
- [Laravel TrustProxies Documentation](https://laravel.com/docs/11.x/requests#configuring-trusted-proxies)
- [Laravel URL Generation](https://laravel.com/docs/11.x/urls)
