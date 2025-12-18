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
                <div class="text-center text-black">
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
            <div v-if="!chatFinalizado" class="flex-shrink-0 border-t-2 border-[#8fbfec] bg-white px-3 text-black md:px-4 py-3 md:py-4">
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

onMounted(async () => {
  // Obtener token CSRF
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

  if (!csrfToken) {
    console.error('Token CSRF no encontrado')
    alert('Error de configuración. Por favor recarga la página.')
    return
  }

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
    authEndpoint: '/broadcasting/auth',
    auth: {
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
    },
  })

  // Delay de 300ms para asegurar que la sesión esté inicializada (fix para Mac)
  await new Promise(resolve => setTimeout(resolve, 300))

  // Crear chat automáticamente al cargar
  crearChatAutomatico()
})

onUnmounted(() => {
  if (channel) {
    channel.stopListening('message.sent')
  }
})

// Obtener token CSRF de forma segura
const getCsrfToken = () => {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}

// Funciones
const crearChatAutomatico = async (retries = 3) => {
  try {
    const csrfToken = getCsrfToken()
    if (!csrfToken) {
      console.error('Token CSRF no encontrado en crearChatAutomatico')
      alert('Error de sesión. Por favor recarga la página.')
      return
    }

    const response = await fetch('/chat/crear-sesion', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })

    if (response.status === 419) {
      // Si hay reintentos disponibles, esperar y reintentar
      if (retries > 0) {
        console.warn(`CSRF error 419, reintentando... (${retries} intentos restantes)`)
        // Esperar 1 segundo antes de reintentar
        await new Promise(resolve => setTimeout(resolve, 1000))
        return crearChatAutomatico(retries - 1)
      }

      // Si ya no hay reintentos, mostrar error y recargar
      console.error('Error 419: Token CSRF inválido o sesión expirada')
      alert('Tu sesión ha expirado. Por favor recarga la página.')
      window.location.reload()
      return
    }

    if (!response.ok) {
      console.error('Error en respuesta:', response.status, response.statusText)
      throw new Error(`HTTP error! status: ${response.status}`)
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
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest'
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
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
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
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest'
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

  try {
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
      .error((error) => {
        console.error('Error en canal de WebSocket:', error)
        // Si es error de autenticación, puede ser sesión expirada
        if (error?.status === 419 || error?.status === 401) {
          alert('Tu sesión ha expirado. Por favor recarga la página.')
          window.location.reload()
        }
      })
  } catch (error) {
    console.error('Error al suscribirse al canal:', error)
  }
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
