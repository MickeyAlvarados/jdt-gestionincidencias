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
./start-dev.sh

# Opción 2: Manual (requiere 4 terminales)
# Terminal 1: Servidor Laravel
php artisan serve

# Terminal 2: Queue worker (procesa mensajes de IA - CRÍTICO)
php artisan queue:work --tries=3

# Terminal 3: Servidor WebSocket (CRÍTICO para chat en tiempo real)
php artisan reverb:start

# Terminal 4: Vite Dev Server (hot reload del frontend)
npm run dev

# Producción: Script automatizado
./start-prod.sh
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
  - **URL API**: https://api.deepseek.com/v1
  - **Modelo**: deepseek-chat
  - **Temperature**: 0.7 (configurable para respuestas consistentes)
  - **Timeout**: 30 segundos
  - **Max tokens**: 1000
  - Construye prompts especializados para soporte IT
  - Evalúa puntuaciones de confianza en la resolución (0-1)
  - Detecta categorías automáticamente:
    - **Hardware**: computadora, pc, laptop, teclado, mouse, monitor, disco, memoria, cpu
    - **Red**: internet, wifi, red, conexión, conectar, ethernet, router, switch
    - **Impresora**: impresora, imprimir, impresión, toner, papel, escáner
    - **Software**: programa, aplicación, software, instalar, actualizar, error
    - **Correo**: correo, email, outlook, gmail, mensaje
    - **Acceso**: contraseña, password, acceso, login, usuario, cuenta, bloqueado
  - Determina si los problemas requieren escalamiento humano

- **`app/Services/AgenteIAService.php`**: Servicio orquestador de nivel superior que:
  - **Flujo inteligente de resolución**:
    1. Busca soluciones similares en `bd_conocimientos` (top 3)
    2. Si encuentra coincidencias → Envía a DeepSeek **CON contexto** de conversaciones previas
    3. Si no encuentra o es segundo intento → Usa IA pura **SIN contexto**
  - Método principal: `procesarProblema($mensaje, $contextoChat, $forzarIA = false)`
  - Tipos de solución:
    - `'ia_con_conocimientos'`: DeepSeek + contexto de BD
    - `'ia'`: DeepSeek pura sin contexto previo
  - Calcula nivel de confianza y categoría del problema
  - Coordina aprendizaje automático guardando soluciones exitosas

#### Controladores

- **`app/Http/Controllers/ChatController.php`**: Maneja todas las operaciones del chat
  - **`crearSesion()`**: Crea nueva sesión de chat automáticamente
    - Finaliza chats anteriores no completados del usuario
    - Genera ID incremental único para el chat
    - Estado inicial: `'iniciado'`
  - **`enviarMensaje($chatId)`**: Procesa mensajes del usuario
    - Valida mensaje (máx 1000 caracteres)
    - Guarda en BD con ID incremental
    - Despacha job `ProcessChatMessage` para procesamiento asíncrono
  - **`obtenerMensajes($chatId)`**: Recupera historial de mensajes
    - Identifica si emisor es IA (`ia@support.local`) o usuario
    - Formatea respuesta con información del emisor
  - **`confirmarResolucion($chatId)`**: Maneja confirmación/rechazo del usuario
    - **Si resuelto=true**:
      - Solución de IA → Guarda en BD conocimientos (aprendizaje automático)
      - Crea incidencia como RESUELTA
      - Finaliza chat (estado: `'resuelto'`)
    - **Si resuelto=false**:
      - Solución de BD → Intenta segundo intento con IA pura (`forzarIA=true`)
      - Solución de IA → Deriva a técnico humano (crea incidencia DERIVADO)
  - **Métodos privados**:
    - `guardarSolucionExitosa()`: Almacena en BD conocimientos
    - `derivarATecnico()`: Crea incidencia y asigna técnico disponible
    - `crearIncidenciaResuelta()`: Registra incidencia resuelta automáticamente
    - `crearDetalleIncidencia*()`: Crea registros de seguimiento

#### Procesamiento Asíncrono

- **`app/Jobs/ProcessChatMessage.php`**: Job de cola que procesa mensajes de usuario
  - **Parámetros**: `$chatId`, `$mensaje`, `$userId`, `$intentoTipo`
  - **Flujo**:
    1. Obtiene contexto del chat (últimos 10 mensajes)
    2. Determina si debe forzar IA (segundo intento)
    3. Llama a `AgenteIAService::procesarProblema()`
    4. Actualiza estado del chat según tipo de solución:
       - BD → `'esperando_feedback_bd'`
       - IA → `'esperando_feedback_ia'`
    5. Guarda mensaje de respuesta de IA en BD
    6. Transmite vía evento `MessageSent` (broadcasting)
  - **Manejo de errores**:
    - Envía mensaje de error al usuario
    - Crea incidencia derivada automáticamente
    - También hace broadcast del mensaje de error
  - **Sistema de colas**: `QUEUE_CONNECTION=database`
  - **Worker**: `php artisan queue:work --tries=3`

#### Sistema de Broadcasting

- **`app/Events/MessageSent.php`**: Transmisión de mensajes en tiempo real
  - **Canal**: PrivateChannel `chat.{chatId}` (autenticado en `routes/channels.php`)
  - **Nombre del evento**: `message.sent`
  - **Datos transmitidos**:
    - id del mensaje
    - contenido
    - fecha_envio
    - emisor (id, nombre, es_ia)
    - metadata (tipo_solucion, fuente, confianza)
  - **Impulsado por**: Laravel Reverb (WebSockets)
  - Se dispara desde `ProcessChatMessage` job después de guardar el mensaje

#### Modelos Clave

- **`Chat`** (`app/Models/Chat.php`): Sesiones de conversación entre usuarios e IA
  - **Campos principales**:
    - `id` (bigInteger, manual, incremental)
    - `user_id` (FK a users)
    - `fecha_chat` (timestamp)
    - `estado_resolucion` (enum): `'iniciado'`, `'esperando_feedback_bd'`, `'esperando_feedback_ia'`, `'resuelto'`, `'derivado'`
    - `intento_actual` (enum, nullable): `'bd_conocimientos'`, `'ia'`, `'derivado'`
    - `solucion_propuesta_id` (FK a bd_conocimientos, nullable)
  - **Relaciones**: `mensajes()`, `usuario()`
  - **Nota**: IDs manuales (`$incrementing = false`)

- **`ChatMensaje`** (`app/Models/ChatMensaje.php`): Mensajes individuales en los chats
  - **Campos principales**:
    - `id` (bigInteger, manual, incremental)
    - `id_chat` (FK a chat)
    - `emisor` (FK a users/empleados)
    - `contenido_mensaje` (text)
    - `fecha_envio` (timestamp)
  - **Métodos**: `esDeIA()` - verifica si emisor es `ia@support.local`
  - **Relaciones**: `chat()`, `emisorRelacion()`
  - **Nota**: IDs manuales (`$incrementing = false`)

- **`BdConocimiento`**: Base de conocimiento de soluciones de las que la IA aprende
  - **Campos principales**:
    - `problema` (text) - Descripción del problema
    - `comentario_resolucion` (text, cast a array) - Conversación completa en formato JSON
    - `resolutor` (string) - Quien resolvió (IA o técnico)
  - **Métodos**: `buscarSolucionesSimilares()` - búsqueda por palabras clave (top 3)
  - **Importante**: `comentario_resolucion` NO es texto plano, es array de mensajes con roles

- **`Incidencia`**: Tickets creados cuando la IA no puede resolver problemas
  - **Estados posibles**: PENDIENTE, DERIVADO, EN_PROCESO, RESUELTO, CERRADO, CANCELADO
  - **Método**: `guardarEnConocimientos()` - aprendizaje automático de soluciones técnicas
  - Se crea automáticamente al derivar o cuando usuario confirma resolución

- **`Categoria`** y **`Estado`**: Categorización y seguimiento de estado para incidencias

### Arquitectura Frontend

**SPA Vue 3 + Inertia.js** con TypeScript y Tailwind CSS.

#### Archivos Frontend Clave

- **`resources/js/app.ts`**: Punto de entrada principal de la aplicación
  - Configuración de Inertia.js
  - Integración de enrutamiento Ziggy
  - Inicialización de tema
  - **Nota**: Echo NO se inicializa aquí, se inicializa en el componente Chat/Index.vue

- **`resources/js/pages/Chat/Index.vue`**: Interfaz de chat en tiempo real
  - **Estado reactivo principal**:
    - `chatId` - ID del chat actual
    - `mensajes` - Array de mensajes del chat
    - `escribiendo` - IA está procesando
    - `esperandoConfirmacion` - Muestra botones de confirmación
    - `chatFinalizado` - Chat terminado
    - `tipoSolucionActual` - `'bd_conocimientos'` o `'ia'`

  - **Métodos principales**:
    - `crearChatAutomatico()` - Se ejecuta en `onMounted()`
      - Verifica token CSRF antes de iniciar
      - Llama a `/chat/crear-sesion` (POST)
      - Maneja errores 419 (sesión expirada con reload)
      - Inicia WebSocket con `escucharMensajes()`

    - `enviarMensaje()` - Envía mensaje del usuario
      - Valida mensaje no vacío
      - POST a `/chat/{chatId}/mensaje`
      - Activa estado "escribiendo"
      - Headers: `X-CSRF-TOKEN`, `Accept`, `X-Requested-With`

    - `confirmarResolucion(resuelto)` - Maneja confirmación/rechazo
      - POST a `/chat/{chatId}/confirmar-resolucion`
      - Parámetros: `resuelto` (boolean), `tipo_solucion`
      - Si `finalizar_chat=true` → Pantalla de cierre
      - Si `finalizar_chat=false` → Continúa con segundo intento

    - `escucharMensajes()` - Configuración de Laravel Echo
      - Inicializa Echo con configuración Reverb
      - Se suscribe a canal privado `chat.{chatId}`
      - Escucha evento `.message.sent`
      - Al recibir mensaje:
        - Desactiva "escribiendo"
        - Activa "esperandoConfirmacion"
        - Guarda `tipo_solucion` de metadata
      - Maneja errores de autenticación (419, 401)

  - **Configuración de Echo**:
    ```javascript
    window.Echo = new Echo({
      broadcaster: 'reverb',
      key: VITE_REVERB_APP_KEY,
      wsHost: VITE_REVERB_HOST,
      wsPort: VITE_REVERB_PORT,
      forceTLS: REVERB_SCHEME === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: '/broadcasting/auth',
      auth: { headers: { 'X-CSRF-TOKEN': token } }
    })
    ```

  - **UI/UX**:
    - Diseño responsive con Tailwind CSS
    - Mensajes diferenciados por color:
      - IA: Blanco con borde azul
      - Usuario: Gradiente azul
    - Indicador de escritura animado (3 círculos pulsantes)
    - Botones de confirmación claros
    - Pantalla de cierre con opción "Iniciar nuevo chat"

  - **Mejoras recientes (commit 955b8fd)**:
    - Extracción anticipada del token CSRF
    - Validación de token antes de inicializar Echo
    - Headers consistentes en todos los fetch
    - Manejo mejorado de error 419 con reload automático
    - Try-catch en `escucharMensajes()` para errores de WebSocket

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

#### 1. INICIO DE CHAT

```
Usuario accede a /chat → Frontend ejecuta crearChatAutomatico()
  ↓
POST /chat/crear-sesion → ChatController::crearSesion()
  ↓
- Finaliza chats previos no completados del usuario
- Crea nuevo Chat (estado: 'iniciado', ID incremental)
- Retorna chat_id
  ↓
Frontend inicializa WebSocket con escucharMensajes()
  ↓
Se suscribe a canal privado 'chat.{chatId}'
```

#### 2. USUARIO ENVÍA MENSAJE

```
Usuario escribe problema → Click "Enviar" → enviarMensaje()
  ↓
POST /chat/{chatId}/mensaje → ChatController::enviarMensaje()
  ↓
- Guarda mensaje del usuario en chat_mensajes (ID incremental)
- Dispatch ProcessChatMessage::dispatch($chatId, $mensaje, $userId)
- Retorna success
  ↓
Frontend muestra indicador "escribiendo..."
```

#### 3. PROCESAMIENTO ASÍNCRONO (Queue Worker)

```
Queue Worker ejecuta ProcessChatMessage job
  ↓
- Obtiene contexto (últimos 10 mensajes)
- Llama a AgenteIAService::procesarProblema()
  ↓
┌─────────────────────────────────────┐
│ 3A. BÚSQUEDA EN BD CONOCIMIENTOS    │
└─────────────────────────────────────┘
BdConocimiento::buscarSolucionesSimilares()
  ↓
¿Hay soluciones similares (top 3)?
  ├─ SÍ → DeepSeek CON contexto de conversaciones previas
  │        - Tipo: 'ia_con_conocimientos'
  │        - Chat estado: 'esperando_feedback_bd'
  │
  └─ NO (o forzarIA=true) → DeepSeek SIN contexto (IA pura)
           - Tipo: 'ia'
           - Chat estado: 'esperando_feedback_ia'
  ↓
DeepSeekService::resolverProblema()
  ↓
- Construye prompt especializado para soporte IT
- POST https://api.deepseek.com/v1/chat/completions
- Evalúa capacidad de resolución
- Calcula confianza (0-1)
- Detecta categoría del problema
  ↓
Job guarda respuesta IA en chat_mensajes
  ↓
Broadcast evento MessageSent con metadata
  ↓
Laravel Reverb transmite mensaje vía WebSocket
```

#### 4. FRONTEND RECIBE RESPUESTA (Tiempo Real)

```
Echo escucha evento '.message.sent'
  ↓
Frontend actualiza UI:
- Desactiva "escribiendo"
- Muestra mensaje de IA
- Activa botones de confirmación
- Guarda tipo_solucion de metadata
```

#### 5. USUARIO CONFIRMA/RECHAZA SOLUCIÓN

```
Usuario hace click en botón → confirmarResolucion(resuelto)
  ↓
POST /chat/{chatId}/confirmar-resolucion
  ↓
ChatController::confirmarResolucion()
  ↓
┌────────────────────────────────────┐
│ CASO A: Usuario dice "SÍ"          │
│ (resuelto = true)                  │
└────────────────────────────────────┘
¿Tipo fue 'ia'?
  ├─ SÍ → guardarSolucionExitosa()
  │        - Extrae problema (primer mensaje usuario)
  │        - Extrae solución (mensajes de IA)
  │        - Guarda en bd_conocimientos (APRENDIZAJE)
  │        - Crea incidencia RESUELTA
  │        - Chat estado: 'resuelto'
  │
  └─ NO (fue 'bd_conocimientos') →
           - Solo crea incidencia RESUELTA
           - NO guarda en conocimientos (ya existe)
           - Chat estado: 'resuelto'
  ↓
Retorna: finalizar_chat = true
  ↓
Frontend muestra pantalla de éxito

┌────────────────────────────────────┐
│ CASO B: Usuario dice "NO"          │
│ (resuelto = false)                 │
└────────────────────────────────────┘
¿Tipo fue 'bd_conocimientos'?
  ├─ SÍ (PRIMER INTENTO FALLÓ) →
  │     - Chat estado: 'esperando_feedback_ia'
  │     - Chat intento_actual: 'ia'
  │     - Obtiene problema original
  │     - Dispatch job con flag 'segundo_intento_ia'
  │     - Job forzará IA pura (forzarIA=true)
  │     - Retorna: finalizar_chat = false
  │     - Frontend sigue en chat esperando respuesta
  │
  └─ NO (fue 'ia', SEGUNDO INTENTO FALLÓ) →
          - derivarATecnico()
          - Crea/actualiza incidencia (estado: DERIVADO)
          - Asigna técnico disponible (rol: TECNICO_INFORMATICA)
          - Crea DetalleIncidencia con comentario
          - Chat estado: 'derivado'
          - Retorna: finalizar_chat = true
          - Frontend muestra pantalla de derivación
```

#### Sistema de Dos Intentos

El sistema implementa un flujo inteligente de 2 intentos:

1. **Primer intento**: Busca en BD conocimientos
   - Si encuentra → Envía a IA CON contexto
   - Si no encuentra → Envía a IA SIN contexto

2. **Si falla el primer intento**:
   - Usuario dice "NO" a solución de BD → Segundo intento con IA pura
   - Usuario dice "NO" a solución de IA pura → Deriva a técnico humano

Este sistema maximiza la posibilidad de resolución automática antes de escalar a humanos.

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
- Temperature: 0.7 (configurable, balanceado entre creatividad y consistencia)
- Max tokens: 1000

### Dependencias NPM Críticas

**Para el sistema de chat en tiempo real:**
```json
"laravel-echo": "^2.2.4"
"pusher-js": "^8.4.0"
"@inertiajs/vue3": "^2.0.0"
```

**Nota importante**: Echo se inicializa SOLO en el componente `Chat/Index.vue`, NO en `app.ts`

## Solución de Problemas

### Problemas de WebSocket

**Error: "websockets:serve command not found"**
- Comando incorrecto. Este proyecto usa **Laravel Reverb**, NO `beyondcode/laravel-websockets`
- Comando correcto: `php artisan reverb:start`
- Ver `SOLUCION_WEBSOCKETS.md` para guía completa de migración

**Error: "WebSocket connection to 'ws://127.0.0.1:6001' failed"**
- **Puerto incorrecto**: Reverb usa puerto 8080 por defecto, no 6001
- Servidor Reverb no ejecutándose: `php artisan reverb:start`
- Credenciales faltantes: Ejecutar `php generate-reverb-credentials.php`
- Limpiar caché de config: `php artisan config:clear`
- Reiniciar Vite: `npm run dev` (variables VITE_* se compilan en JS)

**Error 419: "CSRF token mismatch" en WebSocket auth**
- Sesión expirada: El frontend ahora detecta esto y recarga automáticamente
- Verificar que cookie de sesión está siendo enviada
- Revisar configuración de `SESSION_DOMAIN` en `.env`

**Error: "Connection refused on port 8080"**
- Reverb no está corriendo: Iniciar con `php artisan reverb:start`
- Puerto ocupado: Cambiar `REVERB_PORT` en `.env` o matar proceso en puerto 8080
- Firewall bloqueando: Permitir conexiones en puerto configurado

### Cola No Procesando

**Síntoma: IA no responde, mensajes se quedan "escribiendo..."**

```bash
# Verificar que queue worker está corriendo
ps aux | grep "queue:work"

# Reiniciar queue worker
php artisan queue:restart

# Revisar trabajos fallidos
php artisan queue:failed

# Ver logs en tiempo real
tail -f storage/logs/laravel.log
tail -f storage/logs/queue.log  # Desarrollo
tail -f storage/logs/queue-prod.log  # Producción

# Verificar trabajos pendientes en BD
php artisan queue:monitor database

# Si hay muchos trabajos atascados, limpiar tabla
php artisan queue:flush
```

**CRÍTICO**: El queue worker DEBE estar corriendo para que la IA responda. Sin él, los jobs nunca se procesan.

### Chat No Responde

Verificar en este orden:

1. **Queue Worker corriendo**:
   ```bash
   php artisan queue:work --tries=3
   ```
   Sin esto, la IA NUNCA responderá.

2. **Laravel Reverb corriendo**:
   ```bash
   php artisan reverb:start
   ```
   Sin esto, mensajes no llegarán en tiempo real.

3. **API key de DeepSeek válida**:
   - Verificar en `.env`: `DEEPSEEK_API_KEY=sk-...`
   - Obtener en https://platform.deepseek.com/api_keys
   - Limpiar config cache: `php artisan config:clear`

4. **Revisar logs**:
   ```bash
   # Log general
   tail -f storage/logs/laravel.log

   # Log de cola
   tail -f storage/logs/queue.log

   # Buscar errores específicos
   grep "ERROR" storage/logs/laravel.log
   ```

5. **Revisar tabla de jobs**:
   - Tabla `jobs`: Jobs pendientes
   - Tabla `failed_jobs`: Jobs que fallaron
   - Si hay jobs en `failed_jobs`, ver el stack trace completo

### Problemas de CSRF Token (Recientes - Solucionado)

**Problema**: Sesiones expiraban causando fallo en autenticación de WebSocket

**Solución implementada (commit 955b8fd)**:
- Validación anticipada de token CSRF en `onMounted()`
- Headers consistentes en todos los fetch
- Detección de error 419 con reload automático
- Manejo de errores en channel de Echo

**Si aún tienes problemas**:
- Verificar que `SESSION_DRIVER` está configurado correctamente
- Verificar que dominio de sesión coincide con app
- Limpiar cookies del navegador
- Verificar que `APP_URL` coincide con URL actual

## Servicios Críticos que DEBEN Estar Corriendo

**Para que el sistema de chat funcione, estos 4 servicios son OBLIGATORIOS:**

### 1. Laravel Server (Puerto 8000)
```bash
php artisan serve
```
Sirve la aplicación web. Sin esto, la app no carga.

### 2. Laravel Reverb (Puerto 8080)
```bash
php artisan reverb:start
```
**CRÍTICO**: Servidor WebSocket para mensajería en tiempo real.
- Sin esto: Los mensajes NO llegarán en tiempo real al frontend
- El usuario verá "escribiendo..." indefinidamente

### 3. Queue Worker
```bash
php artisan queue:work --tries=3
```
**CRÍTICO**: Procesa los mensajes de IA de forma asíncrona.
- Sin esto: La IA NUNCA responderá
- Los jobs se quedarán en la tabla `jobs` sin procesar

### 4. Vite Dev Server (Desarrollo)
```bash
npm run dev
```
Hot reload del frontend. En producción usar assets compilados: `npm run build`

**Script automatizado** (recomendado):
```bash
./start-dev.sh  # Linux/Mac - Inicia los 4 servicios automáticamente
./start-prod.sh # Producción - Sin Vite, usa assets compilados
```

## Notas Específicas del Proyecto

### Usuario IA Especial
- **Email**: `ia@support.local`
- **Rol**: AGENTE_IA
- **CRÍTICO**: NUNCA eliminar este usuario, el sistema depende de él
- Se crea automáticamente en el seeding
- Identifica todos los mensajes generados por la IA

### Credenciales por Defecto
- **Admin**: `admin@gmail.com` / `123456`
- **⚠️ CAMBIAR EN PRODUCCIÓN**

### Sistema de IDs Manuales
Los modelos `Chat` y `ChatMensaje` usan IDs manuales incrementales:
- `$incrementing = false`
- Generación: `$id = Model::max('id') + 1`
- Razón: Compatibilidad con esquema de BD existente

### Base de Conocimiento (Aprendizaje Automático)
- Campo `comentario_resolucion` en `bd_conocimientos` almacena **array JSON completo** con conversación
- NO es texto plano, es array de mensajes con roles (user/assistant)
- Se usa para que la IA aprenda de soluciones previas
- El sistema guarda automáticamente resoluciones exitosas de IA

### Sistema de Dos Intentos
1. **Primer intento**: BD conocimientos → Si falla, usuario rechaza
2. **Segundo intento**: IA pura (sin contexto de BD) → Si falla, deriva a técnico
3. **Derivación**: Crea incidencia DERIVADO y asigna técnico disponible

### Estados del Chat
```php
'iniciado'                 // Chat recién creado
'esperando_feedback_bd'    // Esperando confirmación de solución de BD
'esperando_feedback_ia'    // Esperando confirmación de solución de IA
'resuelto'                 // Problema resuelto exitosamente
'derivado'                 // Derivado a técnico humano
```

### Ventana de Contexto
- La IA considera los **últimos 10 mensajes** para contexto en conversaciones
- Incluye mensajes del usuario y respuestas de IA

### Asignación Automática de Incidencias
- Cálculo de prioridad basado en categoría detectada:
  - **Alta**: hardware, red
  - **Baja**: software, correo, acceso
- Asigna automáticamente a técnico disponible (rol: TECNICO_INFORMATICA)
- Usa algoritmo simple: primer técnico encontrado

### Configuración de Producción (Commit 69ca034)
```php
// app/Providers/AppServiceProvider.php
if ($this->app->environment('production') ||
    request()->header('X-Forwarded-Proto') === 'https') {
    URL::forceScheme('https');
}
```
Configurado para trabajar detrás de Cloudflare Tunnel o proxies

### Variables VITE_*
Las variables `VITE_*` se compilan en el JavaScript durante el build:
- Cualquier cambio requiere: `npm run build` (producción) o reiniciar `npm run dev`
- Se leen del `.env` en tiempo de compilación, no en runtime

## Archivos de Configuración Clave

### Backend
- **`config/broadcasting.php`**: Configuración de Reverb como broadcaster
- **`config/reverb.php`**: Configuración del servidor Reverb (puerto, host, etc.)
- **`config/services.php`**: API key de DeepSeek y configuración
- **`routes/channels.php`**: Define canal privado `chat.{chatId}` con autenticación
- **`routes/web.php`**: Rutas del chat (líneas 64-72)

### Frontend
- **`resources/js/pages/Chat/Index.vue`**: Componente principal del chat
- **`resources/js/app.ts`**: Punto de entrada (Echo NO se inicializa aquí)

### Base de Datos
- **`database/migrations/2025_10_02_225645_create_chat_table.php`**
- **`database/migrations/2025_10_02_225759_create_chat_mensajes_table.php`**
- **`database/migrations/2025_10_14_213429_add_estado_resolucion_to_chat_table.php`**

### Scripts
- **`start-dev.sh`**: Inicio rápido para desarrollo (4 servicios)
- **`start-prod.sh`**: Inicio para producción
- **`generate-reverb-credentials.php`**: Generador de credenciales Reverb

## Mejores Prácticas

### Para Desarrollo
- **Siempre** usar `./start-dev.sh` para asegurar que todos los servicios estén corriendo
- Monitorear logs en tiempo real:
  ```bash
  tail -f storage/logs/laravel.log   # Logs generales
  tail -f storage/logs/queue.log     # Jobs procesados
  tail -f storage/logs/reverb.log    # WebSocket (si existe)
  ```
- Limpiar cachés después de cambios en configuración:
  ```bash
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  ```

### Para Producción
- Usar **Supervisor** para gestionar Queue Worker y Reverb como daemons
- Configurar **Nginx** con proxy para WebSocket en `/reverb/*`
- Optimizar cachés:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- Compilar assets de frontend:
  ```bash
  npm run build
  ```
- **Importante**: Variables `VITE_*` se compilan en el JS, requieren rebuild tras cambios

### Monitoreo del Sistema
```bash
# Verificar servicios corriendo
ps aux | grep "php artisan serve"
ps aux | grep "queue:work"
ps aux | grep "reverb:start"

# Verificar logs de errores
grep "ERROR" storage/logs/laravel.log | tail -20

# Verificar jobs fallidos
php artisan queue:failed

# Estado de base de datos
php artisan migrate:status
```

## Cambios Recientes Importantes

### Commit 955b8fd: "cambios problemas con el chat"
**Mejoras en manejo de CSRF Token**:
- Extracción anticipada del token CSRF en `onMounted()`
- Validación de token antes de inicializar Echo
- Headers consistentes en todos los fetch
- Detección explícita de error 419 con reload automático
- Try-catch en `escucharMensajes()` para errores de WebSocket

**Configuración de producción sin SSL**:
- Ajuste de puertos (443 → 80)
- `REVERB_SCHEME=http` para dominio sin SSL
- Configuración de proxy headers en Nginx

### Commit dee2a83: "cambios chat con IA"
**GRAN ACTUALIZACIÓN - 53 archivos**:
- Sistema de chat completo implementado
- Servicios de IA (AgenteIAService, DeepSeekService)
- Jobs y Events (ProcessChatMessage, MessageSent)
- Modelos (Chat, ChatMensaje, BdConocimiento)
- Migración a Laravel Reverb (reemplazo de websockets antiguo)
- Sistema de colas con base de datos
- Usuario especial `ia@support.local`

### Commit 69ca034: "cambios para prod"
**Ajustes para producción**:
- AppServiceProvider con forzado de HTTPS con proxy
- Configuración para Cloudflare Tunnel
- Detección de header `X-Forwarded-Proto`

## Archivos de Documentación

- **`README.md`**: Guía completa de configuración y uso (referencia principal)
- **`SOLUCION_WEBSOCKETS.md`**: Solución rápida para errores de WebSocket
- **`WEBSOCKETS_GUIDE.md`**: Análisis profundo de Reverb vs paquete antiguo de websockets
- **`CHAT_IA_SETUP.md`**: Configuración detallada del sistema de chat con IA
- **`CHECKLIST_VERIFICACION.md`**: Checklist de verificación de instalación
- **`INDICE_DOCUMENTACION.md`**: Índice de navegación de documentación

---

# Rules
- Las vistas deben ser responsive (diseño adaptable para móvil, tablet y desktop)
- Llevar orden en el plan de ejecución (usar TodoWrite tool para planificar)
- Trabajo en sistema operativo Windows (usar comandos compatibles cuando sea necesario)