# PLAN DE MODIFICACIÓN DEL SISTEMA DE CHAT

## 📋 ANÁLISIS DEL CAMBIO REQUERIDO

### FLUJO ACTUAL ❌

```
1. Formulario inicial con descripción del problema
2. Crea chat → Procesa con IA
3. IA decide si deriva o resuelve automáticamente
4. Muestra respuesta → Pide confirmación
5. Si confirma → guarda en BD conocimientos
```

### FLUJO NUEVO SOLICITADO ✅

```
1. Chat abierto directamente (como WhatsApp/Messenger)
2. Usuario escribe problema
3. Sistema busca en BD Conocimientos PRIMERO
   ├─ ¿Existe solución similar?
   │  ├─ SÍ → Propone solución + Pide feedback
   │  │  ├─ Funciona → Guarda incidencia RESUELTA + Finaliza
   │  │  └─ NO funciona → Continúa al paso 4
   │  └─ NO existe → Continúa al paso 4
   │
4. Sistema consulta IA (DeepSeek) SEGUNDO INTENTO
   └─ Propone solución + Pide feedback
      ├─ Funciona → Guarda en BD conocimientos + Incidencia RESUELTA + Finaliza
      └─ NO funciona → Incidencia NO RESUELTA + Deriva a técnico + Finaliza
```

---

## 🎯 PLAN DE IMPLEMENTACIÓN DETALLADO

### FASE 1: MODIFICACIONES DE BASE DE DATOS

**Objetivo**: Agregar campos para trackear el estado del flujo de resolución

#### Archivo: `database/migrations/YYYY_MM_DD_HHMMSS_add_estado_resolucion_to_chat_table.php`

**Acción**: Crear nueva migración

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat', function (Blueprint $table) {
            $table->enum('estado_resolucion', [
                'iniciado',
                'esperando_feedback_bd',
                'esperando_feedback_ia',
                'resuelto',
                'derivado'
            ])->default('iniciado')->after('fecha_chat');

            $table->enum('intento_actual', [
                'bd_conocimientos',
                'ia',
                'derivado'
            ])->nullable()->after('estado_resolucion');

            $table->unsignedBigInteger('solucion_propuesta_id')->nullable()->after('intento_actual');

            // Opcional: Foreign key si quieres mantener integridad referencial
            // $table->foreign('solucion_propuesta_id')->references('id')->on('bd_conocimientos')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('chat', function (Blueprint $table) {
            $table->dropColumn(['estado_resolucion', 'intento_actual', 'solucion_propuesta_id']);
        });
    }
};
```

**Justificación**:
- `estado_resolucion`: Trackea en qué etapa del proceso está el chat
- `intento_actual`: Indica qué tipo de solución se propuso (BD o IA)
- `solucion_propuesta_id`: Referencia a la solución de BD que se propuso (si aplica)

---

### FASE 2: MODIFICACIONES DEL FRONTEND

**Archivo**: `resources/js/pages/Chat/Index.vue`

#### 2.1 Eliminar Formulario Inicial y Mostrar Chat Directo

**Localización**: Líneas 21-47 (sección del formulario inicial)

**ELIMINAR**:
```vue
<!-- Estado inicial: formulario para iniciar chat -->
<div v-if="!chatIniciado" class="flex-1 flex items-center justify-center p-6 bg-gradient-to-b from-[#b8d1e7]/20 to-white">
  <div class="w-full max-w-md space-y-4">
    <div class="bg-white rounded-xl shadow-lg border-2 border-[#8fbfec] p-6">
      <div>
        <Label for="problema" class="text-[#0960ae] font-semibold">Describe tu problema</Label>
        <Textarea
          id="problema"
          v-model="mensajeInicial"
          placeholder="Ej: No puedo imprimir desde mi computadora, la impresora no responde..."
          rows="4"
          class="mt-2 border-2 border-[#8fbfec] focus:border-[#3e8fd8] focus:ring-[#64a6e3]"
        />
      </div>

      <Button
        @click="iniciarChat"
        :disabled="!mensajeInicial.trim() || loading"
        class="w-full mt-4 bg-gradient-to-r from-[#0960ae] to-[#3e8fd8] hover:from-[#3e8fd8] hover:to-[#64a6e3] text-white border-0 shadow-md py-6 text-base font-semibold"
      >
        <Icon v-if="loading" name="loader" class="w-5 h-5 mr-2 animate-spin" />
        <Icon v-else name="message-circle" class="w-5 h-5 mr-2" />
        Iniciar Chat con IA
      </Button>
    </div>
  </div>
</div>
```

**REEMPLAZAR CON**: Nada, el chat activo se mostrará directamente.

#### 2.2 Modificar Sección del Template

**Localización**: Líneas 10-169

**CAMBIAR**:
```vue
<template>
  <AppLayout title="Chat de Soporte">
    <template #header>
      <Heading>
        <Icon name="message-circle" class="w-6 h-6" />
        Chat de Soporte con IA
      </Heading>
    </template>

    <div class="max-w-3xl mx-auto h-[calc(100vh-8rem)] md:h-[calc(100vh-10rem)] flex flex-col px-2 md:px-0">
      <Card class="flex flex-col h-full shadow-lg overflow-hidden">
        <CardHeader class="flex-shrink-0 border-b bg-gradient-to-r from-[#0960ae] via-[#3e8fd8] to-[#64a6e3]">
          <CardTitle class="text-white">¿En qué podemos ayudarte?</CardTitle>
          <CardDescription class="text-[#b8d1e7]">
            Describe tu problema de soporte informático y nuestro agente IA te ayudará a resolverlo.
            Si es necesario, derivaremos tu caso a un técnico especializado.
          </CardDescription>
        </CardHeader>

        <CardContent class="flex-1 flex flex-col overflow-hidden p-0">
          <!-- Chat siempre activo desde el inicio -->
          <div class="flex flex-col h-full">
            <!-- Mensajes -->
            <div class="flex-1 overflow-y-auto px-3 md:px-4 py-4 md:py-6 bg-gradient-to-b from-[#b8d1e7]/20 to-white" ref="messagesContainer">
              <!-- Mensaje de bienvenida si no hay mensajes -->
              <div v-if="mensajes.length === 0" class="flex items-center justify-center h-full">
                <div class="text-center text-[#64a6e3]">
                  <Icon name="message-circle" class="w-16 h-16 mx-auto mb-4 opacity-50" />
                  <p class="text-lg font-semibold">¡Hola! 👋</p>
                  <p class="text-sm mt-2">Cuéntame, ¿en qué puedo ayudarte hoy?</p>
                </div>
              </div>

              <div v-else class="max-w-4xl mx-auto space-y-3 md:space-y-4">
                <div
                  v-for="mensaje in mensajes"
                  :key="mensaje.id"
                  :class="[
                    'flex',
                    mensaje.emisor.es_ia ? 'justify-start' : 'justify-end'
                  ]"
                >
                  <div
                    :class="[
                      'max-w-[75%] md:max-w-md lg:max-w-lg px-4 py-3 rounded-2xl shadow-md',
                      mensaje.emisor.es_ia
                        ? 'bg-white border-2 border-[#8fbfec] text-gray-900 rounded-tl-sm'
                        : 'bg-gradient-to-br from-[#0960ae] to-[#3e8fd8] text-white rounded-tr-sm'
                    ]"
                  >
                    <div class="text-xs font-semibold mb-1.5 opacity-90">
                      {{ mensaje.emisor.nombre }}
                    </div>
                    <div class="text-sm whitespace-pre-wrap leading-relaxed">
                      {{ mensaje.contenido }}
                    </div>
                    <div
                      :class="[
                        'text-xs mt-1.5 text-right',
                        mensaje.emisor.es_ia ? 'text-[#64a6e3]' : 'text-[#b8d1e7]'
                      ]"
                    >
                      {{ formatDate(mensaje.fecha_envio) }}
                    </div>
                  </div>
                </div>

                <!-- Indicador de escritura -->
                <div v-if="escribiendo" class="flex justify-start">
                  <div class="bg-white border-2 border-[#8fbfec] px-4 py-3 rounded-2xl rounded-tl-sm shadow-md">
                    <div class="text-xs font-semibold mb-1.5 text-[#0960ae]">Agente IA</div>
                    <div class="flex space-x-1">
                      <div class="w-2 h-2 bg-[#3e8fd8] rounded-full animate-bounce"></div>
                      <div class="w-2 h-2 bg-[#64a6e3] rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                      <div class="w-2 h-2 bg-[#8fbfec] rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Botones de confirmación -->
            <div v-if="esperandoConfirmacion" class="flex-shrink-0 border-t-2 border-[#8fbfec] bg-gradient-to-r from-[#b8d1e7]/30 to-[#8fbfec]/30 px-3 md:px-4 py-3">
              <div class="max-w-4xl mx-auto">
                <p class="text-sm font-semibold text-[#0960ae] mb-2">¿Te ha sido útil esta respuesta?</p>
                <div class="flex flex-col sm:flex-row gap-2">
                  <Button
                    @click="confirmarResolucion(true)"
                    class="flex-1 text-sm bg-gradient-to-r from-[#0960ae] to-[#3e8fd8] hover:from-[#3e8fd8] hover:to-[#64a6e3] text-white border-0 shadow-md"
                  >
                    <Icon name="check" class="w-4 h-4 mr-2" />
                    <span class="hidden sm:inline">Sí, problema resuelto</span>
                    <span class="sm:hidden">Resuelto</span>
                  </Button>
                  <Button
                    @click="confirmarResolucion(false)"
                    class="flex-1 text-sm bg-white hover:bg-[#b8d1e7] text-[#0960ae] border-2 border-[#64a6e3] shadow-md"
                  >
                    <Icon name="user" class="w-4 h-4 mr-2" />
                    <span class="hidden sm:inline">Necesito ayuda de un técnico</span>
                    <span class="sm:hidden">Necesito técnico</span>
                  </Button>
                </div>
              </div>
            </div>

            <!-- Input de mensaje -->
            <div v-if="!chatFinalizado" class="flex-shrink-0 border-t-2 border-[#8fbfec] bg-white px-3 md:px-4 py-3 md:py-4">
              <div class="max-w-4xl mx-auto flex gap-2">
                <Input
                  v-model="nuevoMensaje"
                  @keyup.enter="enviarMensaje"
                  placeholder="Escribe tu mensaje..."
                  class="flex-1 border-2 border-[#8fbfec] focus:border-[#3e8fd8] focus:ring-[#64a6e3]"
                  :disabled="loading || escribiendo"
                />
                <Button
                  @click="enviarMensaje"
                  :disabled="!nuevoMensaje.trim() || loading || escribiendo"
                  class="h-10 w-10 bg-gradient-to-br from-[#0960ae] to-[#3e8fd8] hover:from-[#3e8fd8] hover:to-[#64a6e3] text-white border-0 shadow-md"
                >
                  <Icon v-if="loading" name="loader" class="w-4 h-4 animate-spin" />
                  <Icon v-else name="send" class="w-4 h-4" />
                </Button>
              </div>
            </div>

            <!-- Mensaje final -->
            <div v-if="chatFinalizado" class="flex-shrink-0 border-t-2 border-[#8fbfec] bg-gradient-to-r from-[#b8d1e7]/20 to-white px-3 md:px-4 py-4 md:py-6">
              <div class="max-w-4xl mx-auto text-center">
                <div v-if="problemaResuelto" class="text-[#0960ae]">
                  <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-[#0960ae] to-[#64a6e3] rounded-full flex items-center justify-center">
                    <Icon name="check-circle" class="w-10 h-10 text-white" />
                  </div>
                  <p class="text-xl font-bold">¡Problema resuelto!</p>
                  <p class="text-sm text-[#64a6e3] mt-2">
                    {{ mensajeFinal || 'La solución ha sido guardada en nuestra base de conocimiento.' }}
                  </p>
                  <Button @click="iniciarNuevoChat" class="mt-4 bg-gradient-to-r from-[#0960ae] to-[#3e8fd8] hover:from-[#3e8fd8] hover:to-[#64a6e3] text-white">
                    Iniciar nuevo chat
                  </Button>
                </div>
                <div v-else class="text-[#0960ae]">
                  <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-[#3e8fd8] to-[#64a6e3] rounded-full flex items-center justify-center">
                    <Icon name="user" class="w-10 h-10 text-white" />
                  </div>
                  <p class="text-xl font-bold">Incidencia derivada</p>
                  <p class="text-sm text-[#64a6e3] mt-2">Un técnico especializado se pondrá en contacto contigo pronto.</p>
                  <Button @click="iniciarNuevoChat" class="mt-4 bg-gradient-to-r from-[#0960ae] to-[#3e8fd8] hover:from-[#3e8fd8] hover:to-[#64a6e3] text-white">
                    Iniciar nuevo chat
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
```

#### 2.3 Modificar Script Setup

**Localización**: Líneas 173-416

**REEMPLAZAR TODO EL SCRIPT CON**:
```vue
<script setup>
import { ref, nextTick, onMounted, onUnmounted } from 'vue'
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

// Componentes
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Card from '@/components/ui/card/Card.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import Icon from '@/components/Icon.vue'

// Estado reactivo
const chatId = ref(null)
const mensajes = ref([])
const nuevoMensaje = ref('')
const loading = ref(false)
const escribiendo = ref(false)
const esperandoConfirmacion = ref(false)
const chatFinalizado = ref(false)
const problemaResuelto = ref(false)
const tipoSolucionActual = ref(null) // 'bd_conocimientos' | 'ia'
const mensajeFinal = ref('')
const messagesContainer = ref(null)

// Echo para WebSockets
let channel = null

onMounted(() => {
  // Inicializar Echo con Reverb
  window.Pusher = Pusher
  window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
  })

  // Crear chat automáticamente al cargar
  crearChatAutomatico()
})

onUnmounted(() => {
  if (channel) {
    channel.stopListening('message.sent')
  }
})

// Funciones
const crearChatAutomatico = async () => {
  try {
    const response = await fetch('/chat/crear-sesion', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      credentials: 'same-origin'
    })

    if (response.status === 419) {
      alert('Tu sesión ha expirado. Por favor recarga la página.')
      window.location.reload()
      return
    }

    const data = await response.json()

    if (data.success) {
      chatId.value = data.chat_id

      // Escuchar mensajes en tiempo real
      escucharMensajes()

      // Cargar mensajes si el chat ya existía
      await cargarMensajes()
    }
  } catch (error) {
    console.error('Error al crear chat:', error)
    alert('Error al iniciar el chat. Por favor recarga la página.')
  }
}

const enviarMensaje = async () => {
  if (!nuevoMensaje.value.trim() || !chatId.value) return

  loading.value = true
  escribiendo.value = true
  const mensaje = nuevoMensaje.value
  nuevoMensaje.value = ''

  try {
    const response = await fetch(`/chat/${chatId.value}/mensaje`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        mensaje: mensaje
      })
    })

    if (response.status === 419) {
      alert('Tu sesión ha expirado. Por favor recarga la página.')
      window.location.reload()
      return
    }

    const data = await response.json()

    if (data.success) {
      await cargarMensajes()
    }
  } catch (error) {
    console.error('Error al enviar mensaje:', error)
    alert('Error al enviar el mensaje. Por favor intenta nuevamente.')
    nuevoMensaje.value = mensaje // Restaurar el mensaje
    escribiendo.value = false
  } finally {
    loading.value = false
  }
}

const cargarMensajes = async () => {
  if (!chatId.value) return

  try {
    const response = await fetch(`/chat/${chatId.value}/mensajes`, {
      credentials: 'same-origin'
    })

    if (response.status === 419) {
      alert('Tu sesión ha expirado. Por favor recarga la página.')
      window.location.reload()
      return
    }

    const data = await response.json()

    if (data.success) {
      mensajes.value = data.mensajes
      await nextTick()
      scrollToBottom()
    }
  } catch (error) {
    console.error('Error al cargar mensajes:', error)
  }
}

const confirmarResolucion = async (resuelto) => {
  if (!chatId.value) return

  loading.value = true

  try {
    const response = await fetch(`/chat/${chatId.value}/confirmar-resolucion`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        resuelto: resuelto,
        tipo_solucion: tipoSolucionActual.value
      })
    })

    if (response.status === 419) {
      alert('Tu sesión ha expirado. Por favor recarga la página.')
      window.location.reload()
      return
    }

    const data = await response.json()

    if (data.success) {
      if (data.finalizar_chat) {
        // Finalizar chat
        esperandoConfirmacion.value = false
        chatFinalizado.value = true
        problemaResuelto.value = resuelto
        mensajeFinal.value = data.mensaje
      } else {
        // Continuar con segundo intento (IA)
        esperandoConfirmacion.value = false
        escribiendo.value = true
      }
    }
  } catch (error) {
    console.error('Error al confirmar resolución:', error)
    alert('Error al confirmar la resolución. Por favor intenta nuevamente.')
  } finally {
    loading.value = false
  }
}

const escucharMensajes = () => {
  if (!chatId.value) return

  channel = window.Echo.private(`chat.${chatId.value}`)
    .listen('.message.sent', (e) => {
      // Agregar mensaje recibido
      mensajes.value.push({
        id: e.id,
        contenido: e.contenido,
        fecha_envio: e.fecha_envio,
        emisor: e.emisor
      })

      // Si es mensaje de IA, mostrar opciones de confirmación
      if (e.emisor.es_ia) {
        escribiendo.value = false
        esperandoConfirmacion.value = true
        tipoSolucionActual.value = e.metadata?.tipo_solucion || 'ia'
      }

      nextTick(() => scrollToBottom())
    })
}

const iniciarNuevoChat = () => {
  // Recargar la página para iniciar nuevo chat
  window.location.reload()
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
```

---

### FASE 3: MODIFICACIONES DEL BACKEND - CONTROLADOR

**Archivo**: `app/Http/Controllers/ChatController.php`

#### 3.1 Añadir Nuevo Método: `crearSesion`

**Localización**: Después del constructor (línea 23)

**AÑADIR**:
```php
/**
 * Crear nueva sesión de chat automáticamente
 */
public function crearSesion(Request $request)
{
    try {
        // Verificar si el usuario ya tiene un chat activo (no finalizado)
        $chatActivo = Chat::whereHas('mensajes', function ($query) {
            $query->where('emisor', Auth::id());
        })
        ->whereIn('estado_resolucion', ['iniciado', 'esperando_feedback_bd', 'esperando_feedback_ia'])
        ->latest('id')
        ->first();

        if ($chatActivo) {
            Log::info('ChatController: Chat activo recuperado', [
                'chat_id' => $chatActivo->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'chat_id' => $chatActivo->id,
                'mensaje' => 'Chat existente recuperado'
            ]);
        }

        // Crear nuevo chat vacío
        DB::beginTransaction();

        $chatId = Chat::max('id') + 1;
        $chat = Chat::create([
            'id' => $chatId,
            'fecha_chat' => now(),
            'estado_resolucion' => 'iniciado',
            'intento_actual' => null,
        ]);

        DB::commit();

        Log::info('ChatController: Nuevo chat creado', [
            'chat_id' => $chat->id,
            'user_id' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'chat_id' => $chat->id,
            'mensaje' => 'Chat creado correctamente'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('ChatController: Error creando sesión de chat', [
            'error' => $e->getMessage(),
            'user_id' => Auth::id()
        ]);

        return response()->json([
            'success' => false,
            'mensaje' => 'Error al crear la sesión de chat'
        ], 500);
    }
}
```

#### 3.2 Eliminar Método `iniciarChat`

**Localización**: Líneas 28-79

**ACCIÓN**: Eliminar todo el método `iniciarChat()`

#### 3.3 Modificar Método `confirmarResolucion`

**Localización**: Líneas 168-240

**REEMPLAZAR CON**:
```php
/**
 * Confirmar resolución del problema con nuevo flujo
 */
public function confirmarResolucion(Request $request, $chatId)
{
    $request->validate([
        'resuelto' => 'required|boolean',
        'tipo_solucion' => 'required|in:bd_conocimientos,ia',
        'comentario' => 'nullable|string|max:500'
    ]);

    try {
        $chat = Chat::findOrFail($chatId);
        $resuelto = $request->input('resuelto');
        $tipoSolucion = $request->input('tipo_solucion');

        if ($resuelto) {
            // ============================================
            // CASO 1: Usuario confirma que SÍ funcionó
            // ============================================

            if ($tipoSolucion === 'ia') {
                // Si fue IA quien resolvió, guardar en BD conocimientos (APRENDIZAJE)
                $this->guardarSolucionExitosa($chatId, $request->input('comentario'));

                Log::info('ChatController: Solución de IA confirmada y guardada en BD', [
                    'chat_id' => $chatId,
                    'user_id' => Auth::id()
                ]);
            }

            // Crear/actualizar incidencia como RESUELTA
            $this->crearIncidenciaResuelta($chatId);

            // Actualizar estado del chat
            $chat->update(['estado_resolucion' => 'resuelto']);

            Log::info('ChatController: Problema resuelto confirmado', [
                'chat_id' => $chatId,
                'tipo_solucion' => $tipoSolucion,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'finalizar_chat' => true,
                'mensaje' => $tipoSolucion === 'ia'
                    ? '¡Excelente! He aprendido de esta solución para ayudar mejor en el futuro.'
                    : '¡Problema resuelto con éxito!'
            ]);

        } else {
            // ============================================
            // CASO 2: Usuario indica que NO funcionó
            // ============================================

            if ($tipoSolucion === 'bd_conocimientos') {
                // Primera solución (BD) no funcionó → Intentar con IA
                $chat->update([
                    'estado_resolucion' => 'esperando_feedback_ia',
                    'intento_actual' => 'ia'
                ]);

                // Obtener el problema original (primer mensaje del usuario)
                $problemaOriginal = $this->obtenerProblemaOriginal($chatId);

                // Procesar con IA en segundo intento (flag especial)
                \App\Jobs\ProcessChatMessage::dispatch(
                    $chatId,
                    $problemaOriginal,
                    Auth::id(),
                    'segundo_intento_ia'
                );

                Log::info('ChatController: Solución BD no funcionó, intentando con IA', [
                    'chat_id' => $chatId,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => true,
                    'finalizar_chat' => false,
                    'mensaje' => 'Entendido. Déjame intentar con otra solución...'
                ]);

            } else {
                // tipoSolucion === 'ia'
                // Segunda solución (IA) tampoco funcionó → Derivar a técnico
                $this->derivarATecnico($chatId, $request->input('comentario'));
                $chat->update(['estado_resolucion' => 'derivado']);

                Log::info('ChatController: Ambas soluciones fallaron, derivando a técnico', [
                    'chat_id' => $chatId,
                    'user_id' => Auth::id()
                ]);

                return response()->json([
                    'success' => true,
                    'finalizar_chat' => true,
                    'mensaje' => 'He derivado tu caso a un técnico especializado que se pondrá en contacto contigo pronto.'
                ]);
            }
        }

    } catch (\Exception $e) {
        Log::error('ChatController: Error al confirmar resolución', [
            'error' => $e->getMessage(),
            'chat_id' => $chatId,
            'user_id' => Auth::id()
        ]);

        return response()->json([
            'success' => false,
            'mensaje' => 'Error al procesar la confirmación'
        ], 500);
    }
}
```

#### 3.4 Añadir Métodos Auxiliares Nuevos

**Localización**: Antes del método `getUsuarioIA()` (línea 365)

**AÑADIR**:
```php
/**
 * Obtener el problema original (primer mensaje del usuario)
 */
private function obtenerProblemaOriginal($chatId): string
{
    $primerMensaje = ChatMensaje::where('id_chat', $chatId)
        ->where('emisor', '!=', $this->getUsuarioIA()->id)
        ->orderBy('fecha_envio', 'asc')
        ->first();

    return $primerMensaje ? $primerMensaje->contenido_mensaje : 'Problema sin descripción';
}

/**
 * Crear incidencia resuelta
 */
private function crearIncidenciaResuelta($chatId)
{
    try {
        // Verificar si ya existe incidencia
        $incidencia = Incidencia::where('id_chat', $chatId)->first();

        if ($incidencia) {
            // Actualizar existente
            $incidencia->update(['estado' => 4]); // Resuelto

            Log::info('ChatController: Incidencia actualizada a resuelta', [
                'incidencia_id' => $incidencia->id
            ]);
        } else {
            // Crear nueva
            $primerMensaje = ChatMensaje::where('id_chat', $chatId)
                ->where('emisor', Auth::id())
                ->orderBy('fecha_envio', 'asc')
                ->first();

            $incidencia = Incidencia::create([
                'descripcion_problema' => $primerMensaje->contenido_mensaje ?? 'Problema resuelto por IA',
                'fecha_incidencia' => now(),
                'id_chat' => $chatId,
                'idempleado' => Auth::id(),
                'estado' => 4, // Resuelto
                'prioridad' => 1,
            ]);

            Log::info('ChatController: Nueva incidencia creada como resuelta', [
                'incidencia_id' => $incidencia->id
            ]);
        }

        return $incidencia;

    } catch (\Exception $e) {
        Log::error('ChatController: Error creando incidencia resuelta', [
            'error' => $e->getMessage(),
            'chat_id' => $chatId
        ]);
    }
}
```

---

### FASE 4: MODIFICACIONES DEL BACKEND - SERVICIO IA

**Archivo**: `app/Services/AgenteIAService.php`

#### 4.1 Modificar Método `procesarProblema`

**Localización**: Líneas 28-97

**REEMPLAZAR CON**:
```php
/**
 * Procesar problema del usuario con lógica de IA inteligente
 *
 * @param string $problema Descripción del problema
 * @param array $contexto Contexto adicional (historial, usuario, etc.)
 * @param bool $forzarIA Si es true, salta búsqueda en BD y usa directamente IA
 * @return array Respuesta estructurada con solución y metadatos
 */
public function procesarProblema(string $problema, array $contexto = [], bool $forzarIA = false): array
{
    Log::info('AgenteIA: Procesando problema', [
        'problema' => substr($problema, 0, 100),
        'forzar_ia' => $forzarIA
    ]);

    // ================================================================
    // PASO 1: Buscar en base de conocimientos (si no es segundo intento)
    // ================================================================
    if (!$forzarIA) {
        $solucionesConocidas = $this->consultarBaseConocimientos($problema);

        if (!empty($solucionesConocidas)) {
            Log::info('AgenteIA: Solución encontrada en BD conocimientos', [
                'cantidad_soluciones' => count($solucionesConocidas),
                'solucion_id' => $solucionesConocidas[0]['id'] ?? null
            ]);

            return [
                'respuesta' => $this->formatearRespuestaConocimiento($solucionesConocidas[0]),
                'tipo_solucion' => 'bd_conocimientos',
                'fuente' => 'base_conocimientos',
                'categoria_detectada' => $this->detectarCategoria($problema),
                'requiere_derivacion' => false,
                'solucion_id' => $solucionesConocidas[0]['id'] ?? null,
                'metadata' => [
                    'confianza' => 0.9,
                    'origen' => 'bd_conocimientos',
                    'fecha_solucion' => $solucionesConocidas[0]['fecha'] ?? null
                ]
            ];
        }
    }

    // ================================================================
    // PASO 2: No hay solución en BD o es segundo intento → Consultar IA
    // ================================================================
    $categoria = $this->detectarCategoria($problema);
    $contextoEnriquecido = $this->enriquecerContexto($problema, $contexto, $categoria);
    $respuestaIA = $this->deepSeekService->resolverProblema($problema, $contextoEnriquecido);

    // PASO 3: Evaluar respuesta de IA
    $confianza = $this->calcularConfianza($respuestaIA);

    Log::info('AgenteIA: Respuesta generada por DeepSeek', [
        'confianza' => $confianza,
        'categoria' => $categoria,
        'longitud_respuesta' => strlen($respuestaIA['respuesta'])
    ]);

    return [
        'respuesta' => $respuestaIA['respuesta'],
        'tipo_solucion' => 'ia',
        'fuente' => 'deepseek',
        'categoria_detectada' => $categoria,
        'requiere_derivacion' => false, // Ya NO deriva automáticamente
        'metadata' => [
            'confianza' => $confianza,
            'origen' => 'deepseek',
            'categoria' => $categoria
        ]
    ];
}
```

#### 4.2 Eliminar Lógica de Derivación Automática

**Localización**: Líneas 52-68

**ELIMINAR**:
```php
// ELIMINAR TODO este bloque:
// PASO 3: Si es categoría crítica, derivar inmediatamente
if ($esCritica) {
    Log::info('AgenteIA: Categoría crítica detectada, derivando', ['categoria' => $categoria]);

    return [
        'respuesta' => $this->generarMensajeDerivacion($categoria),
        'puede_resolver' => false,
        'confianza' => 0,
        'fuente' => 'derivacion_automatica',
        'categoria_detectada' => $categoria,
        'requiere_derivacion' => true,
        'motivo_derivacion' => 'categoria_critica',
    ];
}
```

#### 4.3 Eliminar Métodos No Necesarios

**ELIMINAR MÉTODOS COMPLETOS**:
- `esCategoriaCritica()` (líneas 167-171)
- `generarMensajeDerivacion()` (líneas 173-186)
- `evaluarDerivacionPorContexto()` (líneas 326-372)
- `detectarFeedbackNegativo()` (líneas 377-402)

---

### FASE 5: MODIFICACIONES DEL BACKEND - JOB

**Archivo**: `app/Jobs/ProcessChatMessage.php`

#### 5.1 Modificar Constructor

**Localización**: Líneas 21-30

**REEMPLAZAR CON**:
```php
protected $chatId;
protected $mensaje;
protected $userId;
protected $intentoTipo; // NUEVO

/**
 * Create a new job instance.
 */
public function __construct($chatId, $mensaje, $userId, $intentoTipo = 'primer_intento')
{
    $this->chatId = $chatId;
    $this->mensaje = $mensaje;
    $this->userId = $userId;
    $this->intentoTipo = $intentoTipo; // NUEVO
}
```

#### 5.2 Modificar Método `handle`

**Localización**: Líneas 32-163

**REEMPLAZAR CON**:
```php
public function handle(AgenteIAService $agenteIAService)
{
    try {
        Log::info('ProcessChatMessage: Iniciando procesamiento', [
            'chat_id' => $this->chatId,
            'user_id' => $this->userId,
            'intento_tipo' => $this->intentoTipo
        ]);

        // Obtener contexto del chat
        $contexto = $this->obtenerContextoChat();

        // Determinar si forzar IA (segundo intento)
        $forzarIA = ($this->intentoTipo === 'segundo_intento_ia');

        // Procesar con el Agente IA
        $respuestaIA = $agenteIAService->procesarProblema(
            $this->mensaje,
            $contexto,
            $forzarIA // Nuevo parámetro
        );

        // Actualizar estado del chat según tipo de solución
        $chat = Chat::find($this->chatId);
        if ($respuestaIA['tipo_solucion'] === 'bd_conocimientos') {
            $chat->update([
                'estado_resolucion' => 'esperando_feedback_bd',
                'intento_actual' => 'bd_conocimientos',
                'solucion_propuesta_id' => $respuestaIA['solucion_id'] ?? null
            ]);
        } else {
            $chat->update([
                'estado_resolucion' => 'esperando_feedback_ia',
                'intento_actual' => 'ia'
            ]);
        }

        // Guardar respuesta de IA
        $mensajeId = ChatMensaje::max('id') ?? 0;
        $mensajeId++;
        $mensajeIA = ChatMensaje::create([
            'id' => $mensajeId,
            'id_chat' => $this->chatId,
            'emisor' => $this->getUsuarioIA()->id,
            'contenido_mensaje' => $respuestaIA['respuesta'],
            'fecha_envio' => now(),
        ]);

        // Broadcast del mensaje
        broadcast(new MessageSent([
            'id' => $mensajeIA->id,
            'chat_id' => $this->chatId,
            'contenido' => $respuestaIA['respuesta'],
            'fecha_envio' => $mensajeIA->fecha_envio,
            'emisor' => [
                'id' => $this->getUsuarioIA()->id,
                'nombre' => 'Agente IA',
                'es_ia' => true
            ],
            'metadata' => [
                'tipo_solucion' => $respuestaIA['tipo_solucion'], // NUEVO
                'fuente' => $respuestaIA['fuente'],
                'confianza' => $respuestaIA['metadata']['confianza'] ?? 0
            ]
        ]))->toOthers();

        Log::info('ProcessChatMessage: Procesamiento completado exitosamente', [
            'chat_id' => $this->chatId,
            'tipo_solucion' => $respuestaIA['tipo_solucion'],
            'fuente' => $respuestaIA['fuente']
        ]);

    } catch (\Exception $e) {
        Log::error('ProcessChatMessage: Error procesando mensaje', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'chat_id' => $this->chatId,
            'mensaje' => $this->mensaje
        ]);

        // Mensaje de error
        $mensajeErrorId = ChatMensaje::max('id') ?? 0;
        $mensajeErrorId++;
        $mensajeError = ChatMensaje::create([
            'id' => $mensajeErrorId,
            'id_chat' => $this->chatId,
            'emisor' => $this->getUsuarioIA()->id,
            'contenido_mensaje' => 'Lo siento, ha ocurrido un error procesando tu mensaje. Un técnico se pondrá en contacto contigo pronto.',
            'fecha_envio' => now(),
        ]);

        // Broadcast del mensaje de error
        broadcast(new MessageSent([
            'id' => $mensajeError->id,
            'chat_id' => $this->chatId,
            'contenido' => $mensajeError->contenido_mensaje,
            'fecha_envio' => $mensajeError->fecha_envio,
            'emisor' => [
                'id' => $this->getUsuarioIA()->id,
                'nombre' => 'Agente IA',
                'es_ia' => true
            ],
            'metadata' => [
                'tipo_solucion' => 'error',
                'fuente' => 'sistema'
            ]
        ]))->toOthers();

        // Crear incidencia de error
        $this->crearIncidenciaDerivada([
            'categoria_detectada' => 'error_sistema',
            'motivo_derivacion' => 'error_procesamiento'
        ]);
    }
}
```

#### 5.3 Eliminar Código de Derivación Automática

**Localización**: Líneas 75-116

**ELIMINAR**:
```php
// ELIMINAR todo este bloque:
// Si requiere derivación, crear incidencia
if ($respuestaIA['requiere_derivacion']) {
    $this->crearIncidenciaDerivada($respuestaIA);
    ...
}

// Evaluar derivación por contexto (feedback negativo, timeout)
$evaluacionContexto = $agenteIAService->evaluarDerivacionPorContexto($this->chatId);
if ($evaluacionContexto['debe_derivar']) {
    ...
}
```

---

### FASE 6: MODIFICACIONES DE RUTAS

**Archivo**: `routes/web.php`

**Localización**: Líneas 50-54

**REEMPLAZAR CON**:
```php
Route::prefix('chat')->name('chat.')->middleware('auth')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('index');
    Route::post('/crear-sesion', [ChatController::class, 'crearSesion'])->name('crear-sesion'); // NUEVO
    Route::post('/{chatId}/mensaje', [ChatController::class, 'enviarMensaje'])->name('mensaje');
    Route::get('/{chatId}/mensajes', [ChatController::class, 'obtenerMensajes'])->name('mensajes');
    Route::post('/{chatId}/confirmar-resolucion', [ChatController::class, 'confirmarResolucion'])->name('confirmar-resolucion');
});
```

**ELIMINAR**:
```php
// ELIMINAR esta línea:
Route::post('/iniciar', [ChatController::class, 'iniciarChat'])->name('iniciar');
```

---

### FASE 7: AJUSTES EN MODELOS

#### Archivo: `app/Models/Chat.php`

**Localización**: Líneas 11-18

**REEMPLAZAR CON**:
```php
protected $fillable = [
    'id',
    'fecha_chat',
    'estado_resolucion',      // NUEVO
    'intento_actual',         // NUEVO
    'solucion_propuesta_id',  // NUEVO
];

protected $casts = [
    'fecha_chat' => 'datetime',
];
```

#### Archivo: `app/Models/BdConocimiento.php`

**Sin cambios** - El modelo ya tiene el método `buscarSolucionesSimilares()` implementado correctamente.

---

## 📊 RESUMEN DE CAMBIOS POR ARCHIVO

| Archivo | Tipo de Cambio | Líneas Afectadas | Complejidad |
|---------|----------------|------------------|-------------|
| **Nueva migración** | Crear archivo nuevo | +45 | 🟢 Baja |
| **Index.vue** | Modificación mayor | ~400 líneas | 🟡 Media |
| **ChatController.php** | Modificación + nuevos métodos | ~200 líneas | 🟡 Media |
| **AgenteIAService.php** | Modificación + eliminaciones | ~150 líneas | 🟡 Media |
| **ProcessChatMessage.php** | Modificación moderada | ~100 líneas | 🟢 Baja |
| **web.php** | Cambio menor | 2 líneas | 🟢 Baja |
| **Chat.php** | Añadir campos fillable | 3 líneas | 🟢 Baja |

**Total estimado**: ~900 líneas de código modificadas/añadidas

---

## ✅ RESULTADO ESPERADO

### **Experiencia de Usuario Final**:

```
┌─────────────────────────────────────────────────────┐
│ FLUJO COMPLETO DEL NUEVO SISTEMA                    │
└─────────────────────────────────────────────────────┘

1. Usuario entra a /chat
   ✓ Chat ya está abierto (sin formulario inicial)
   ✓ Input listo para escribir

2. Usuario escribe: "No puedo imprimir desde mi computadora"
   ✓ Sistema busca en BD conocimientos
   ✓ Encuentra solución similar (ejemplo de hace 2 semanas)

   📱 IA responde:
   "He encontrado una solución similar en nuestra base de conocimientos:

   **Problema similar:** Impresora no responde desde Windows

   **Solución:**
   1. Verifica que la impresora esté encendida
   2. Ve a Configuración → Impresoras
   3. Elimina la impresora y agrégala de nuevo
   4. Reinicia el servicio de cola de impresión

   _Esta solución fue proporcionada por: Juan Pérez_"

   [✓ Sí, funcionó] [✗ No funcionó]

3a. Si usuario clickea [✓ Sí, funcionó]:
   ✓ Crea incidencia estado = "Resuelta"
   ✓ Chat finaliza
   ✓ Mensaje: "¡Problema resuelto!"
   ✓ Botón "Iniciar nuevo chat"

3b. Si usuario clickea [✗ No funcionó]:
   ✓ Sistema consulta DeepSeek IA
   ✓ Muestra indicador "escribiendo..."

   📱 IA responde (desde DeepSeek):
   "Entendido. Déjame probar otra solución:

   1. Presiona Win + R, escribe 'services.msc'
   2. Busca 'Cola de impresión'
   3. Detén el servicio
   4. Ve a C:\Windows\System32\spool\PRINTERS
   5. Elimina todos los archivos
   6. Reinicia el servicio
   7. Intenta imprimir de nuevo"

   [✓ Sí, funcionó] [✗ No funcionó]

4a. Si usuario clickea [✓ Sí, funcionó]:
   ✓ Guarda solución en BD conocimientos (APRENDIZAJE)
   ✓ Crea incidencia estado = "Resuelta"
   ✓ Chat finaliza
   ✓ Mensaje: "¡Excelente! He aprendido esta solución"
   ✓ Botón "Iniciar nuevo chat"

4b. Si usuario clickea [✗ No funcionó]:
   ✓ Crea incidencia estado = "No Resuelta"
   ✓ Asigna a técnico disponible
   ✓ Chat finaliza
   ✓ Mensaje: "Derivado a técnico especializado"
   ✓ Botón "Iniciar nuevo chat"
```

---

## 🚀 VENTAJAS DEL NUEVO SISTEMA

| Característica | Ventaja |
|----------------|---------|
| **Experiencia UX** | Chat instantáneo como WhatsApp/Messenger |
| **Optimización BD** | Respuestas inmediatas sin consumir API |
| **Ahorro de costos** | Solo llama a DeepSeek si BD no tiene solución |
| **Dos intentos** | Mayor probabilidad de resolución (BD + IA) |
| **Feedback granular** | Usuario decide en cada paso si funcionó |
| **Aprendizaje continuo** | Cada solución exitosa de IA se guarda en BD |
| **Derivación inteligente** | Solo deriva después de 2 intentos fallidos |
| **Tracking completo** | Estados claros en cada etapa del proceso |

---

## 📝 ORDEN DE EJECUCIÓN RECOMENDADO

```bash
# 1. Crear y ejecutar migración
php artisan make:migration add_estado_resolucion_to_chat_table
# (Copiar código de FASE 1)
php artisan migrate

# 2. Modificar modelos (FASE 7)
# Editar: app/Models/Chat.php

# 3. Modificar servicios backend (FASE 4)
# Editar: app/Services/AgenteIAService.php

# 4. Modificar job (FASE 5)
# Editar: app/Jobs/ProcessChatMessage.php

# 5. Modificar controlador (FASE 3)
# Editar: app/Http/Controllers/ChatController.php

# 6. Modificar rutas (FASE 6)
# Editar: routes/web.php

# 7. Modificar frontend (FASE 2)
# Editar: resources/js/pages/Chat/Index.vue

# 8. Compilar assets
npm run build

# 9. Limpiar cachés
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 10. Reiniciar servicios
# Terminal 1: php artisan serve
# Terminal 2: php artisan queue:work
# Terminal 3: php artisan reverb:start
# Terminal 4: npm run dev
```

---

## 🧪 TESTING SUGERIDO

### Test Manual

1. **Escenario 1: Solución en BD Conocimientos funciona**
   - Entrar a /chat
   - Escribir problema que existe en BD
   - Verificar que muestra solución de BD
   - Confirmar que funcionó
   - Verificar incidencia creada como "Resuelta"

2. **Escenario 2: Solución en BD no funciona, IA funciona**
   - Escribir problema que existe en BD
   - Rechazar solución de BD
   - Esperar respuesta de IA
   - Confirmar que funcionó
   - Verificar que se guardó en BD conocimientos

3. **Escenario 3: Ninguna solución funciona, derivación**
   - Escribir problema nuevo
   - Rechazar solución de IA
   - Verificar derivación a técnico
   - Verificar incidencia creada como "No Resuelta"

### Test Unitario Sugerido

```php
// tests/Feature/ChatFlowTest.php
public function test_chat_se_crea_automaticamente()
{
    $response = $this->post('/chat/crear-sesion');
    $response->assertStatus(200);
    $response->assertJsonStructure(['success', 'chat_id']);
}

public function test_solucion_bd_tiene_prioridad_sobre_ia()
{
    // Crear solución en BD
    // Enviar problema similar
    // Verificar que usa BD en lugar de API
}
```

---

## ⚠️ CONSIDERACIONES IMPORTANTES

1. **Migración de Datos**: Los chats existentes no tendrán los nuevos campos. Considera ejecutar un seeder para actualizar registros antiguos.

2. **Compatibilidad**: Asegúrate de que todos los archivos estén sincronizados antes de hacer commit.

3. **WebSockets**: Verificar que Reverb esté ejecutándose correctamente antes de probar.

4. **Queue Worker**: Debe estar ejecutándose para que los mensajes de IA funcionen.

5. **API Key**: DeepSeek API key debe estar configurada en `.env`.

---

## 📚 DOCUMENTACIÓN RELACIONADA

- `README.md` - Guía completa del proyecto
- `CLAUDE.md` - Instrucciones para Claude Code

---

**Fecha de creación**: 2025-01-14
**Versión del plan**: 1.0
**Estado**: Listo para implementación
