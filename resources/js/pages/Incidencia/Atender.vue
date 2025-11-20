<template>
  <ToastProvider>
    <DialogRoot v-model:open="isOpen">
      <DialogPortal>
        <DialogOverlay class="fixed inset-0 bg-black/50 z-50" />
        <DialogContent
          class="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg shadow-xl z-50 w-full max-w-3xl max-h-[90vh] overflow-y-auto"
        >
          <div class="p-6">
            <!-- Header -->
            <DialogTitle class="text-2xl font-bold text-gray-900 mb-2">
              Atender Incidencia
            </DialogTitle>
            <DialogDescription class="text-sm text-gray-600 mb-6">
              Incidencia #{{ incidencia?.id }} - {{ incidencia?.descripcion_problema }}
            </DialogDescription>

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
              <nav class="-mb-px flex space-x-8">
                <button
                  @click="activeTab = 'atender'"
                  :class="[
                    'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
                    activeTab === 'atender'
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  ]"
                >
                  <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Atender
                </button>
                <button
                  @click="activeTab = 'historial'"
                  :class="[
                    'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
                    activeTab === 'historial'
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  ]"
                >
                  <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Historial
                </button>
              </nav>
            </div>

            <!-- Tab Content: Atender -->
            <div v-show="activeTab === 'atender'">
              <form @submit.prevent="guardarAtencion">
                <!-- Radio Buttons: Resolver o Derivar -->
                <div class="mb-6">
                  <Label class="text-base font-semibold text-gray-700 mb-3 block">
                    Tipo de Atención
                  </Label>
                  <div class="space-y-3">
                    <div class="flex items-center">
                      <input
                        id="resolver"
                        v-model="tipoAtencion"
                        type="radio"
                        value="resolver"
                        class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                      />
                      <label for="resolver" class="ml-3 text-sm font-medium text-gray-700 cursor-pointer">
                        Resolver Incidencia
                      </label>
                    </div>
                    <div class="flex items-center">
                      <input
                        id="derivar"
                        v-model="tipoAtencion"
                        type="radio"
                        value="derivar"
                        class="w-4 h-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                      />
                      <label for="derivar" class="ml-3 text-sm font-medium text-gray-700 cursor-pointer">
                        Derivar a otra Area
                      </label>
                    </div>
                  </div>
                </div>

                <!-- Campo Descripción de Resolución (si selecciona "Resolver") -->
                <div v-if="tipoAtencion === 'resolver'" class="mb-6">
                  <Label for="descripcion_resolucion" class="text-sm font-medium text-gray-700 mb-2 block">
                    Descripción de la Resolución *
                  </Label>
                  <textarea
                    id="descripcion_resolucion"
                    v-model="form.descripcion_resolucion"
                    rows="4"
                    :class="[
                      'w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500',
                      errors.descripcion_resolucion ? 'border-red-500' : 'border-gray-300'
                    ]"
                    placeholder="Describe cómo se resolvió la incidencia..."
                  ></textarea>
                  <p v-if="errors.descripcion_resolucion" class="mt-1 text-sm text-red-600">
                    {{ errors.descripcion_resolucion }}
                  </p>
                </div>

                <!-- Select Cargo (si selecciona "Derivar") -->
                <div v-if="tipoAtencion === 'derivar'" class="mb-6">
                  <Label for="cargo_id" class="text-sm font-medium text-gray-700 mb-2 block">
                    Derivar a Area *
                  </Label>
                   <textarea
                    id="descripcion_derivar"
                    v-model="form.descripcion_derivar"
                    rows="4"
                    :class="[
                      'w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500',
                      errors.descripcion_derivar ? 'border-red-500' : 'border-gray-300'
                    ]"
                    placeholder="Describe cómo se derivo la incidencia..."
                  ></textarea>
                   <p v-if="errors.descripcion_derivar" class="mt-1 text-sm text-red-600">
                    {{ errors.descripcion_derivar }}
                  </p>
                  <select
                    id="cargo_id"
                    v-model="form.cargo_id"
                    :class="[
                      'w-full px-3 py-2 border rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500',
                      errors.cargo_id ? 'border-red-500' : 'border-gray-300'
                    ]"
                  >
                    <option value="">Seleccione un area</option>
                    <option v-for="cargo in cargos" :key="cargo.id" :value="cargo.id">
                      {{ cargo.descripcion }}
                    </option>
                  </select>
                  <p v-if="errors.cargo_id" class="mt-1 text-sm text-red-600">
                    {{ errors.cargo_id }}
                  </p>
                </div>

                <!-- Botones -->
                <div class="flex gap-3 justify-end pt-4 border-t">
                  <Button
                    type="button"
                    variant="outline"
                    @click="cerrarModal"
                    class="px-4 py-2"
                    :disabled="isSubmitting"
                  >
                    Cancelar
                  </Button>
                  <Button
                    type="submit"
                    :disabled="isSubmitting"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="isSubmitting" class="flex items-center">
                      <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                      Procesando...
                    </span>
                    <span v-else>
                      {{ tipoAtencion === 'resolver' ? 'Resolver' : 'Derivar' }}
                    </span>
                  </Button>
                </div>
              </form>
            </div>

            <!-- Tab Content: Historial -->
            <div v-show="activeTab === 'historial'">
              <div v-if="loadingHistorial" class="flex justify-center items-center py-12">
                <svg class="w-8 h-8 animate-spin text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
              </div>

              <div v-else-if="historial.length === 0" class="text-center py-12">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-gray-600">No hay historial de atenciones</p>
              </div>

              <div v-else class="space-y-4">
                <div
                  v-for="(item, index) in historial"
                  :key="item.id"
                  class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition"
                >
                  <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2">
                      <span
                        :class="[
                          'px-2 py-1 rounded-full text-xs font-semibold',
                          getEstadoAtencionClass(item.estado_text)
                        ]"
                      >
                        {{ getEstadoAtencionLabel(item.estado_text) }}
                      </span>
                    </div>
                    <span class="text-xs text-gray-500">
                      {{ formatDateTime(item.fecha_inicio) }}
                    </span>
                  </div>

                  <div class="space-y-2 text-sm">
                    <div v-if="item.comentarios" class="bg-gray-50 p-3 rounded">
                      <p class="font-medium text-gray-700 mb-1">Comentarios:</p>
                      <p class="text-gray-600">{{ item.comentarios }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                      <div v-if="item.cargo">
                        <p class="font-medium text-gray-700">Area:</p>
                        <p class="text-gray-600">{{ item.cargo.descripcion }}</p>
                      </div>

                      <div v-if="item.empleado_informatica">
                        <p class="font-medium text-gray-700">Atendido por:</p>
                        <p class="text-gray-600">
                          {{ item.empleado_informatica.usuario?.nombres }}
                          {{ item.empleado_informatica.usuario?.apellidos }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <DialogClose
            class="absolute top-4 right-4 p-1 rounded-full hover:bg-gray-100 transition"
            aria-label="Cerrar"
          >
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </DialogClose>
        </DialogContent>
      </DialogPortal>
    </DialogRoot>

    <!-- Toast notifications -->
    <ToastRoot
      v-model:open="toastOpen"
      :class="[
        'rounded-md p-4 shadow-lg fixed bottom-4 right-4 max-w-xs text-white z-[100]',
        toastType === 'success' ? 'bg-green-500' : 'bg-red-500'
      ]"
    >
      <ToastTitle class="font-bold">{{ toastType === 'success' ? 'Éxito' : 'Error' }}</ToastTitle>
      <ToastDescription>{{ toastMessage }}</ToastDescription>
    </ToastRoot>

    <ToastViewport
      class="[--viewport-padding:_25px] fixed bottom-0 right-0 flex flex-col p-[var(--viewport-padding)] gap-[10px] w-[390px] max-w-[100vw] z-[100]"
    />
  </ToastProvider>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  DialogRoot,
  DialogPortal,
  DialogOverlay,
  DialogContent,
  DialogTitle,
  DialogDescription,
  DialogClose,
} from 'reka-ui'
import { ToastProvider, ToastRoot, ToastTitle, ToastDescription, ToastViewport } from 'reka-ui'
import Button from '@/components/ui/button/Button.vue'
import Label from '@/components/ui/label/Label.vue'

const props = defineProps({
  cargos: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['incidenciaAtendida'])

const isOpen = ref(false)
const incidencia = ref(null)
const tipoAtencion = ref('resolver')
const isSubmitting = ref(false)
const activeTab = ref('atender')
const historial = ref([])
const loadingHistorial = ref(false)

const toastOpen = ref(false)
const toastMessage = ref('')
const toastType = ref('success')

const form = ref({
  descripcion_resolucion: '',
  descripcion_derivar: '',
  cargo_id: ''
})

const errors = ref({
  descripcion_resolucion: '',
  descripcion_derivar: '',
  cargo_id: ''
})

const showToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  toastOpen.value = false
  setTimeout(() => {
    toastOpen.value = true
  }, 50)
}

// Limpiar el formulario cuando cambia el tipo de atención
watch(tipoAtencion, () => {
  form.value = {
    descripcion_resolucion: '',
    descripcion_derivar: '',
    cargo_id: ''
  }
  errors.value = {
    descripcion_resolucion: '',
    descripcion_derivar: '',
    cargo_id: ''
  }
})

// Cargar historial cuando se cambia a la tab de historial
watch(activeTab, (newTab) => {
  if (newTab === 'historial' && incidencia.value) {
    cargarHistorial()
  }
})

const cargarHistorial = async () => {
  loadingHistorial.value = true
  try {
    const response = await fetch(`/incidencias/historial/${incidencia.value.id}`)
    if (response.ok) {
      const data = await response.json()
      console.log(data,33)
      historial.value = data.historial
    } else {
      showToast('Error al cargar el historial', 'error')
    }
  } catch (error) {
    console.error('Error al cargar historial:', error)
    showToast('Error al cargar el historial', 'error')
  } finally {
    loadingHistorial.value = false
  }
}

const abrirModal = (incidenciaSeleccionada) => {
  incidencia.value = incidenciaSeleccionada
  tipoAtencion.value = 'resolver'
  activeTab.value = 'atender'
  historial.value = []
  form.value = {
    descripcion_resolucion: '',
    descripcion_derivar: '',
    cargo_id: ''
  }
  errors.value = {
    descripcion_resolucion: '',
    descripcion_derivar: '',
    cargo_id: ''
  }
  isOpen.value = true
}

const cerrarModal = () => {
  isOpen.value = false
  incidencia.value = null
  tipoAtencion.value = 'resolver'
  activeTab.value = 'atender'
  historial.value = []
  form.value = {
    descripcion_resolucion: '',
    descripcion_derivar: '',
    cargo_id: ''
  }
  errors.value = {
    descripcion_resolucion: '',
    descripcion_derivar: '',
    cargo_id: ''
  }
}

const validarFormulario = () => {
  errors.value = {
    descripcion_resolucion: '',
    descripcion_derivar: '',
    cargo_id: ''
  }

  let esValido = true

  if (tipoAtencion.value === 'resolver') {
    if (!form.value.descripcion_resolucion || !form.value.descripcion_resolucion.trim()) {
      errors.value.descripcion_resolucion = 'La descripción de la resolución es requerida'
    //   showToast('La descripción de la resolución es requerida', 'error')
      esValido = false
    } else if (form.value.descripcion_resolucion.trim().length < 10) {
      errors.value.descripcion_resolucion = 'La descripción debe tener al menos 10 caracteres'
    //   showToast('La descripción debe tener al menos 10 caracteres', 'error')
      esValido = false
    }
  }

  if (tipoAtencion.value === 'derivar') {
    if (!form.value.descripcion_derivar || !form.value.descripcion_derivar.trim()) {
      errors.value.descripcion_derivar = 'La descripción de la derivación es requerida'
    //   showToast('La descripción de la derivación es requerida', 'error')
      esValido = false
    } else if (form.value.descripcion_derivar.trim().length < 10) {
      errors.value.descripcion_derivar = 'La descripción debe tener al menos 10 caracteres'
    //   showToast('La descripción debe tener al menos 10 caracteres', 'error')
      esValido = false
    }
    if (!form.value.cargo_id || form.value.cargo_id === '') {
      errors.value.cargo_id = 'Debe seleccionar un cargo'
    //   showToast('Debe seleccionar un cargo para derivar', 'error')
      esValido = false
    }
  }

  return esValido
}

const guardarAtencion = async () => {
  if (!validarFormulario()) {
    return
  }

  isSubmitting.value = true

  try {
    const data = {
      tipo_atencion: tipoAtencion.value,
      incidencia_id: incidencia.value.id
    }

    if (tipoAtencion.value === 'resolver') {
      data.descripcion_resolucion = form.value.descripcion_resolucion.trim()
    } else if (tipoAtencion.value === 'derivar') {
      data.cargo_id = form.value.cargo_id
      data.descripcion_derivar = form.value.descripcion_derivar.trim()
    }

    const response = await fetch('/incidencias/atender', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(data)
    })

    if (response.ok) {
      const result = await response.json()
      showToast(
        tipoAtencion.value === 'resolver'
          ? 'Incidencia resuelta exitosamente'
          : 'Incidencia derivada exitosamente',
        'success'
      )
      cerrarModal()

      setTimeout(() => {

        emit('incidenciaAtendida')
      }, 1000)
    } else {
      const errorData = await response.json()
      showToast(errorData.mensaje || 'Error al atender la incidencia', 'error')
    }
  } catch (error) {
    console.log('Error:', error.response)

    showToast(error.message || 'Error al procesar la solicitud', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const formatDateTime = (dateTime) => {
  if (!dateTime) return 'N/A'
  const date = new Date(dateTime)
  return date.toLocaleString('es-ES', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getEstadoAtencionLabel = (estado) => {
  const map = {
    'Pendiente': 'Pendiente',
    'En Proceso': 'En Proceso',
    'Derivado': 'Derivado',
    'Resuelto': 'Resuelto'
  }
  return map[estado] || estado
}

const getEstadoAtencionClass = (estado) => {
  const map = {
    'Pendiente': 'bg-blue-100 text-blue-800',
    'Derivado': 'bg-orange-100 text-orange-800',
    'En Proceso': 'bg-yellow-100 text-yellow-800',
    'Resuelto': 'bg-green-100 text-green-800',
    'Cerrado': 'bg-red-100 text-red-800',
    'Cancelado': 'bg-gray-100 text-gray-800'
  }
  return map[estado] || 'bg-gray-100 text-gray-800'
}

defineExpose({ abrirModal })
</script>
