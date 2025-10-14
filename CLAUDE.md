# CLAUDE.md

Este archivo proporciona orientación a Claude Code (claude.ai/code) cuando trabaja con código en este repositorio.

## Descripción del Proyecto

**JDT-GestiónIncidencias** es un sistema de gestión de incidencias técnicas construido con Laravel 11, Inertia.js, Vue 3 y PostgreSQL. El sistema cuenta con un sistema de chat de soporte con IA que maneja automáticamente problemas técnicos usando DeepSeek AI, con escalamiento automático a técnicos humanos cuando es necesario.

## Comandos Esenciales

### Configuración de Desarrollo

```bash
# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configuración de base de datos (PostgreSQL)
php artisan migrate
php artisan db:seed

# Generar credenciales WebSocket de Reverb
php generate-reverb-credentials.php
```

### Ejecutar la Aplicación

```bash
# Opción 1: Script automatizado (recomendado)
# Windows PowerShell
.\start-dev.ps1

# Linux/Mac
chmod +x start-dev.sh
./start-dev.sh

# Opción 2: Manual (requiere 3 terminales)
# Terminal 1: Servidor Laravel
php artisan serve

# Terminal 2: Queue worker (procesa mensajes de IA)
php artisan queue:work --tries=3

# Terminal 3: Servidor WebSocket
php artisan reverb:start
```

### Desarrollo Frontend

```bash
# Modo desarrollo con hot reload
npm run dev

# Build de producción
npm run build

# Linting y formateo
npm run lint
npm run format
npm run format:check
```

### Testing

```bash
# Ejecutar todos los tests
php artisan test
composer test

# Limpiar cachés antes de testear
php artisan config:clear
```

### Operaciones de Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate

# Revertir última migración
php artisan migrate:rollback

# Base de datos fresca con datos de prueba
php artisan migrate:fresh --seed

# Crear nueva migración
php artisan make:migration create_table_name

# Crear seeder
php artisan make:seeder NameSeeder
```

## Visión General de la Arquitectura

### Arquitectura Backend

**Aplicación Laravel 11** con puente Inertia.js hacia frontend Vue 3.

#### Capa de Servicios Core

- **`app/Services/DeepSeekService.php`**: Integración directa con la API de DeepSeek AI para resolución de problemas
  - Construye prompts especializados para soporte IT
  - Evalúa puntuaciones de confianza en la resolución
  - Detecta categorías de problemas (impresora, red, hardware, software)
  - Determina si los problemas requieren escalamiento humano

- **`app/Services/AgenteIAService.php`** (referenciado en ProcessChatMessage.php): Servicio de nivel superior que:
  - Consulta la base de conocimiento (`bd_conocimientos`) para soluciones existentes
  - Recurre a la API de DeepSeek para problemas nuevos
  - Gestiona la evaluación de contexto para escalamiento automático
  - Coordina entre la base de conocimiento y respuestas de IA

#### Procesamiento Asíncrono

- **`app/Jobs/ProcessChatMessage.php`**: Job de cola que procesa mensajes de usuario
  - Recupera contexto del chat (últimos 10 mensajes)
  - Llama a AgenteIAService para generar respuesta de IA
  - Transmite respuestas vía WebSockets (evento MessageSent)
  - Crea registros `Incidencia` cuando se necesita escalamiento
  - Asigna automáticamente incidencias a técnicos disponibles (rol: TECNICO_INFORMATICA)
  - Maneja escalamiento contextual (feedback negativo, timeouts)

#### Sistema de Broadcasting

- **`app/Events/MessageSent.php`**: Transmisión de mensajes en tiempo real
  - Usa canales privados: `chat.{chatId}`
  - Nombre del evento: `message.sent`
  - Impulsado por Laravel Reverb (WebSockets)

#### Modelos Clave

- **`Chat`**: Sesiones de conversación entre usuarios e IA
- **`ChatMensaje`**: Mensajes individuales en los chats
- **`Incidencia`**: Tickets creados cuando la IA no puede resolver problemas
- **`BdConocimiento`**: Base de conocimiento de soluciones de las que la IA aprende
- **`Categoria`** y **`Estado`**: Categorización y seguimiento de estado para incidencias

### Arquitectura Frontend

**SPA Vue 3 + Inertia.js** con TypeScript y Tailwind CSS.

#### Archivos Frontend Clave

- **`resources/js/app.ts`**: Punto de entrada principal de la aplicación
  - Configuración de Inertia.js
  - Integración de enrutamiento Ziggy
  - Inicialización de tema

- **`resources/js/pages/Chat/Index.vue`**: Interfaz de chat en tiempo real
  - Cliente Laravel Echo para conexiones WebSocket
  - Escucha el canal privado `chat.{chatId}`
  - Maneja envío y recepción de mensajes
  - Flujo de confirmación de resolución

#### Sistema de Componentes UI

Ubicado en `resources/js/components/ui/`, construido sobre **reka-ui** con:
- Componentes Button, Dialog, Dropdown, Sheet
- Sistema de navegación Sidebar
- Componentes Card, Avatar, Badge
- Usa **class-variance-authority** para gestión de variantes

### Configuración de WebSocket

**Crítico**: Este proyecto usa **Laravel Reverb**, NO `beyondcode/laravel-websockets`.

- **Servidor**: `php artisan reverb:start` (puerto por defecto 8080)
- **Driver de broadcasting**: `reverb`
- **Cliente**: Laravel Echo con Pusher.js
- **Configuración**: `config/broadcasting.php` y `config/reverb.php`
- **Credenciales**: Generar usando `php generate-reverb-credentials.php`

**Variables de entorno requeridas:**
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
QUEUE_CONNECTION=database
```

### Flujo del Sistema de Chat con IA

1. Usuario envía mensaje → **`ChatController::enviarMensaje()`**
2. Mensaje guardado en base de datos → Despacha job **`ProcessChatMessage`**
3. Job se ejecuta asíncronamente:
   - Consulta **`BdConocimiento`** para soluciones similares (vía AgenteIAService)
   - Si no hay coincidencia, consulta **API de DeepSeek**
   - Evalúa si la respuesta resuelve el problema o necesita escalamiento
   - Transmite respuesta vía evento **`MessageSent`**
4. Si necesita escalamiento → Crea **`Incidencia`** y asigna a técnico
5. Frontend recibe actualización en tiempo real vía Laravel Echo

### Sistema de Permisos

Usa el paquete **Spatie Laravel Permission**:
- Roles: ADMINISTRADOR, DOCENTE, TECNICO_INFORMATICA, AGENTE_IA
- Middleware personalizado: `role`, `permission`, `role_or_permission`, `docente.permission`
- Usuario IA: `ia@support.local` con rol AGENTE_IA (usuario del sistema, sin login)

### Organización de Rutas

**`routes/web.php`** contiene:
- Rutas de autenticación (vía `routes/auth.php`)
- Rutas de configuración (vía `routes/settings.php`)
- Canales de broadcasting (vía `routes/channels.php`)
- Controladores de recursos con prefijos personalizados (users, roles, modulos, permissions)
- Rutas de chat: `/chat`, `/chat/iniciar`, `/chat/{chatId}/mensaje`
- Rutas públicas de asistencia QR (sin autenticación requerida)

## Convenciones Importantes

### Base de Datos

- **Conexión primaria**: PostgreSQL
- **Nomenclatura de esquema**: snake_case para tablas y columnas
- **Migraciones**: Incluir tipos enum para campos de estado
- **Orden de seeders**: Roles → Users → Modules → Estados/Categorias → BdConocimiento

### Estilo de Código

- **Backend**: Estándares PSR-12, usar Laravel Pint para formateo
- **Frontend**: ESLint + Prettier configurados
  - `npm run format` para auto-corregir
  - Plugins de Prettier: organize-imports, tailwindcss

### Integración de API

**Configuración de DeepSeek AI** (`config/services.php`):
- API Key requerida de https://platform.deepseek.com/api_keys
- Modelo por defecto: `deepseek-chat`
- Timeout: 30 segundos
- Temperature: 0.3 (para respuestas consistentes de soporte IT)

## Solución de Problemas

### Problemas de WebSocket

**Error: "websockets:serve command not found"**
- Comando incorrecto. Usar `php artisan reverb:start` en su lugar
- Ver `SOLUCION_WEBSOCKETS.md` para guía completa de migración

**Error: "WebSocket connection to 'ws://127.0.0.1:6001' failed"**
- Servidor Reverb no ejecutándose: `php artisan reverb:start`
- Credenciales faltantes: Ejecutar `php generate-reverb-credentials.php`
- Limpiar caché de config: `php artisan config:clear`
- Reiniciar Vite: `npm run dev`

### Cola No Procesando

```bash
# Reiniciar queue worker
php artisan queue:restart

# Revisar trabajos fallidos
php artisan queue:failed

# Ver logs
tail -f storage/logs/laravel.log
```

### Chat No Responde

1. Verificar API key de DeepSeek en `.env`
2. Revisar que queue worker esté ejecutándose: `php artisan queue:work`
3. Revisar que servidor Reverb esté ejecutándose: `php artisan reverb:start`
4. Revisar tabla de trabajos de cola en base de datos para jobs pendientes/fallidos

## Notas Específicas del Proyecto

- **Usuario IA**: El sistema crea el usuario `ia@support.local` (rol: AGENTE_IA) durante el seeding - nunca eliminar este usuario
- **Admin por Defecto**: `admin@gmail.com` / `123456` (cambiar en producción)
- **Asignación Automática de Incidencias**: Cálculo de prioridad basado en categoría (hardware/red = alta, software = baja)
- **Aprendizaje de Base de Conocimiento**: El sistema almacena resoluciones exitosas de IA en `bd_conocimientos` para referencia futura
- **Ventana de Contexto**: La IA considera los últimos 10 mensajes para contexto en conversaciones
- **Disparadores de Escalamiento**: Feedback negativo, sin resolver después de 5 interacciones, o la IA indica explícitamente necesidad de intervención humana

## Archivos de Documentación

- **`README.md`**: Guía completa de configuración y uso (referencia principal)
- **`SOLUCION_WEBSOCKETS.md`**: Solución rápida para errores de WebSocket
- **`WEBSOCKETS_GUIDE.md`**: Análisis profundo de Reverb vs paquete antiguo de websockets
- **`CHAT_IA_SETUP.md`**: Configuración detallada del sistema de chat con IA
- **`CHECKLIST_VERIFICACION.md`**: Checklist de verificación de instalación
- **`INDICE_DOCUMENTACION.md`**: Índice de navegación de documentación


# Rules
- las vistas que sean responsive
- llevar orden el plan de ejecucion para que no te satures