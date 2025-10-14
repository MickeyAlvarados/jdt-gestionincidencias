# JDT-GestiónIncidencias

Sistema de gestión de incidencias técnicas con **chat IA automatizado** para soporte técnico. Construido con Laravel 11, Inertia.js, Vue 3 y PostgreSQL.

## Características

- Gestión completa de incidencias técnicas
- Chat interactivo con IA (DeepSeek) para soporte automatizado
- Derivación automática a técnicos cuando la IA no puede resolver
- Base de conocimiento que aprende de soluciones exitosas
- Mensajería en tiempo real con WebSockets (Laravel Reverb)
- Sistema de roles y permisos
- Interfaz moderna con Vue 3 + Tailwind CSS

## Stack Tecnológico

**Backend:**
- Laravel 11
- PostgreSQL 13+
- Laravel Reverb (WebSockets)
- Laravel Queues (procesamiento asíncrono)
- DeepSeek API (IA)
- Spatie Laravel Permission

**Frontend:**
- Vue 3 + TypeScript
- Inertia.js
- Tailwind CSS
- Laravel Echo + Pusher.js

## Requisitos

- PHP 8.2+
- Composer 2.x
- Node.js 18.x+
- NPM 9.x+
- PostgreSQL 13.x+
- API Key de DeepSeek (obtener en https://platform.deepseek.com/api_keys)

## Instalación Rápida

```bash
# 1. Clonar repositorio
git clone <url-del-repositorio>
cd jdt-gestionincidencias

# 2. Instalar dependencias
composer install
npm install

# 3. Configurar entorno
cp .env.example .env

# Editar .env y configurar:
# - DB_DATABASE, DB_USERNAME, DB_PASSWORD (PostgreSQL)
# - DEEPSEEK_API_KEY (obtener de platform.deepseek.com)
# - QUEUE_CONNECTION=database
# - BROADCAST_CONNECTION=reverb

# 4. Generar claves
php artisan key:generate
php generate-reverb-credentials.php

# Copiar el output del comando anterior en tu .env

# 5. Base de datos
createdb jdt-gestionincidencias  # O crear desde pgAdmin
php artisan migrate --seed

# 6. Compilar assets
npm run build
```

## Levantar el Proyecto

### Opción 1: Script Automático (Recomendado)

**Windows PowerShell:**
```powershell
.\start-dev.ps1
```

**Linux/Mac:**
```bash
chmod +x start-dev.sh
./start-dev.sh
```

### Opción 2: Manual (3 terminales)

```bash
# Terminal 1 - Servidor Laravel (API)
php artisan serve

# Terminal 2 - WebSocket Server (tiempo real)
php artisan reverb:start

# Terminal 3 - Queue Worker (procesa mensajes de IA)
php artisan queue:work --tries=3
```

**Acceso:** http://localhost:8000

**Usuario por defecto:** admin@gmail.com / 123456

## Cómo Funciona el Sistema

### Flujo del Chat con IA

```
1. Usuario describe problema técnico
   ↓
2. Sistema busca en base de conocimiento
   ↓
3. Si no encuentra solución → Consulta DeepSeek API
   ↓
4. IA responde con solución en tiempo real
   ↓
5. Usuario confirma si resolvió el problema
   ↓
   ├─ Resuelto → Guarda solución en base conocimiento
   └─ No resuelto → Crea incidencia y asigna a técnico
```

### Componentes Clave

**Backend:**
- `ChatController` → Maneja conversaciones
- `ProcessChatMessage` (Job) → Procesa mensajes asíncronamente en background
- `DeepSeekService` → Integración con API de DeepSeek
- `AgenteIAService` → Lógica de decisión (base conocimiento vs API)
- `MessageSent` (Event) → Broadcasting para tiempo real

**Frontend:**
- `resources/js/pages/Chat/Index.vue` → Interfaz del chat
- Laravel Echo → Cliente WebSocket

**Base de Datos:**
- `chat` → Sesiones de conversación
- `chat_mensajes` → Mensajes individuales
- `bd_conocimientos` → Base de conocimiento (aprendizaje)
- `incidencias` → Tickets generados cuando IA no resuelve

### Derivación Automática

La IA deriva a técnico cuando:
- Categoría crítica (hardware, red, servidor, seguridad, base de datos)
- Usuario indica que el problema no se resolvió
- Más de 5 interacciones sin resolver
- La IA detecta que necesita intervención humana

## Estructura del Proyecto

```
jdt-gestionincidencias/
├── app/
│   ├── Console/Commands/      # Comandos Artisan personalizados
│   ├── Events/
│   │   └── MessageSent.php    # Evento de broadcasting
│   ├── Http/Controllers/
│   │   └── ChatController.php # Controlador del chat
│   ├── Jobs/
│   │   └── ProcessChatMessage.php  # Job asíncrono
│   ├── Models/                # Eloquent models
│   │   ├── Chat.php
│   │   ├── ChatMensaje.php
│   │   ├── BdConocimiento.php
│   │   └── Incidencia.php
│   └── Services/              # Servicios de negocio
│       ├── DeepSeekService.php
│       └── AgenteIAService.php
├── database/
│   ├── migrations/            # Migraciones de BD
│   └── seeders/
│       ├── RoleSeeder.php     # Crea usuario IA y roles
│       ├── BdConocimientoSeeder.php
│       └── DatabaseSeeder.php
├── resources/
│   └── js/
│       ├── pages/Chat/
│       │   └── Index.vue      # Interfaz del chat
│       └── components/ui/     # Componentes reutilizables
├── routes/
│   ├── web.php                # Rutas principales
│   └── channels.php           # Autorización de canales WebSocket
├── config/
│   ├── broadcasting.php       # Configuración de Reverb
│   ├── reverb.php
│   └── services.php           # API de DeepSeek
├── .env.example               # Template de configuración
├── start-dev.ps1              # Script de inicio Windows
├── start-dev.sh               # Script de inicio Linux/Mac
└── generate-reverb-credentials.php  # Genera credenciales WebSocket
```

## Configuración de Variables de Entorno

**Base de datos:**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=jdt-gestionincidencias
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

**DeepSeek AI:**
```env
DEEPSEEK_API_KEY=sk-tu_api_key_aqui
DEEPSEEK_API_URL=https://api.deepseek.com/v1
DEEPSEEK_MODEL=deepseek-chat
DEEPSEEK_MAX_TOKENS=1000
DEEPSEEK_TEMPERATURE=0.7
DEEPSEEK_TIMEOUT=30
```

**Laravel Reverb (WebSockets):**
```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=771174
REVERB_APP_KEY=mqzwicixdzcofb4odlex
REVERB_APP_SECRET=n5viqeqrksxvxf26eg3j
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**Colas:**
```env
QUEUE_CONNECTION=database
```

## Comandos Útiles

### Desarrollo
```bash
# Iniciar servidor de desarrollo
php artisan serve

# Compilar assets en desarrollo (hot reload)
npm run dev

# Compilar para producción
npm run build

# Limpiar cachés
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Base de Datos
```bash
# Ejecutar migraciones
php artisan migrate

# Refrescar BD con datos de prueba
php artisan migrate:fresh --seed

# Crear nueva migración
php artisan make:migration nombre_migracion

# Crear seeder
php artisan make:seeder NombreSeeder
```

### Colas y Jobs
```bash
# Ver trabajos en cola
php artisan queue:work

# Ver trabajos fallidos
php artisan queue:failed

# Reintentar trabajos fallidos
php artisan queue:retry all

# Reiniciar workers
php artisan queue:restart
```

### WebSockets
```bash
# Iniciar servidor Reverb
php artisan reverb:start

# Reiniciar Reverb
php artisan reverb:restart

# Generar credenciales
php generate-reverb-credentials.php
```

## Troubleshooting

### Error: "websockets:serve command not found"

**Causa:** Estás usando el comando del paquete obsoleto `beyondcode/laravel-websockets`.

**Solución:** Usa el comando correcto de Laravel Reverb:
```bash
php artisan reverb:start
```

Laravel 11+ usa **Reverb** (solución oficial), no el paquete de terceros.

### Error: "WebSocket connection failed"

**Verifica:**
1. Servidor Reverb está corriendo: `php artisan reverb:start`
2. Variables en `.env`:
   ```env
   BROADCAST_CONNECTION=reverb
   REVERB_PORT=8080
   VITE_REVERB_PORT=8080
   ```
3. Limpia caché: `php artisan config:clear`
4. Reinicia Vite: `npm run dev`
5. Limpia caché del navegador (Ctrl+Shift+R)

### Error: "Connection to PostgreSQL refused"

```bash
# Verificar servicio PostgreSQL
# Windows: Servicios → PostgreSQL
# Linux: sudo systemctl status postgresql

# Verificar credenciales en .env
php artisan config:clear
```

### Mensajes de IA no llegan

**Verifica:**
1. Queue worker corriendo: `php artisan queue:work`
2. API Key de DeepSeek en `.env`
3. Revisar logs: `tail -f storage/logs/laravel.log`
4. Revisar trabajos fallidos: `php artisan queue:failed`

### Error: "Port 8080 already in use"

```bash
# Cambiar puerto en .env
REVERB_PORT=8081
VITE_REVERB_PORT=8081

# Reiniciar servicios
php artisan config:clear
php artisan reverb:start
npm run dev
```

### Errores de permisos

```bash
# Windows (ejecutar como administrador)
icacls storage /grant Everyone:F /t
icacls bootstrap/cache /grant Everyone:F /t

# Linux/Mac
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## Usuario IA del Sistema

El sistema crea automáticamente un usuario IA durante el seeding:

- **Email:** ia@support.local
- **Rol:** AGENTE_IA
- **Función:** Responder mensajes de chat automáticamente

**IMPORTANTE:** No eliminar este usuario, es necesario para el funcionamiento del chat.

## Arquitectura del Sistema

```
┌─────────────┐
│   Usuario   │
└──────┬──────┘
       │
       ▼
┌────────────────────────────┐
│  Frontend Vue 3 + Echo     │
│  WebSocket Client          │
└──────┬─────────────────────┘
       │ ws://localhost:8080
       ▼
┌────────────────────────────┐
│  Laravel Reverb            │
│  (WebSocket Server)        │
└──────┬─────────────────────┘
       │
       ▼
┌────────────────────────────┐
│  Laravel Backend           │
│  - ChatController          │
│  - ProcessChatMessage Job  │
│  - MessageSent Event       │
└──────┬─────────────────────┘
       │
       ├────────────┬──────────┐
       ▼            ▼          ▼
┌──────────┐  ┌─────────┐  ┌──────────┐
│PostgreSQL│  │ Queue   │  │ DeepSeek │
│          │  │ Worker  │  │ API (IA) │
└──────────┘  └─────────┘  └──────────┘
```

## Checklist de Verificación

Antes de considerar el sistema funcional, verifica:

- [ ] PostgreSQL corriendo y BD creada
- [ ] Variables `.env` configuradas (DB, DeepSeek, Reverb, Queue)
- [ ] Migraciones ejecutadas: `php artisan migrate --seed`
- [ ] Usuario IA existe: `ia@support.local`
- [ ] 3 servicios corriendo: serve, reverb, queue
- [ ] Puedes acceder a http://localhost:8000
- [ ] Puedes iniciar sesión (admin@gmail.com / 123456)
- [ ] El chat responde en http://localhost:8000/chat
- [ ] No hay errores en consola del navegador (F12)
- [ ] Los mensajes se actualizan en tiempo real

## Contribución

1. Fork el proyecto
2. Crea una rama: `git checkout -b feature/nueva-funcionalidad`
3. Commit: `git commit -am 'Agrega nueva funcionalidad'`
4. Push: `git push origin feature/nueva-funcionalidad`
5. Abre un Pull Request

## Licencia

MIT License

## Soporte

Para problemas o preguntas:
- Crear un issue en el repositorio
- Revisar logs: `storage/logs/laravel.log`
- Contactar al equipo de desarrollo

---

**Desarrollado con Laravel 11 para la gestión eficiente de incidencias técnicas y soporte informático**
