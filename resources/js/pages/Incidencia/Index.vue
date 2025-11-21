<template>
  <AppLayout title="Incidencias">
    <template #header>
      <Heading>
        <Icon name="alert-circle" class="w-6 h-6" />
        Gestion de Incidencias
      </Heading>
    </template>

    <ToastProvider>
      <div class="px-4 py-6 sm:px-6 lg:px-8">
        <!-- Encabezado con botones de acción -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h2 class="text-2xl font-bold text-gray-900">Incidencias</h2>
            <p class="text-sm text-gray-600 mt-1">Gestiona todas las incidencias del sistema</p>
          </div>
          <div class="flex gap-2">
            <button
              @click="verIncidencia"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-500 hover:bg-green-600">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
              Ver
            </button>
            <button
              @click="atenderIncidencia"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-500 hover:bg-blue-600">
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Atender
            </button>
          </div>
        </div>

        <!-- Filtros y busqueda -->
        <Card class="mb-6">
          <CardContent class="pt-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <!-- Busqueda -->
              <div>
                <Label for="search">Buscar</Label>
                <Input
                  id="search"
                  v-model="filters.search"
                  type="text"
                  placeholder="Buscar por descripcion o usuario..."
                  class="mt-1"
                />
              </div>

              <!-- Filtro Estado -->
              <div>
                <Label for="estado">Estado</Label>
                <select
                  id="estado"
                  v-model="filters.estado"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border px-3 py-2"
                >
                  <option value="">Todos los estados</option>
                  <option v-for="estado in estados" :key="estado.id" :value="estado.id">
                    {{ estado.descripcion }}
                  </option>
                </select>
              </div>

              <!-- Filtro Prioridad -->
              <div>
                <Label for="prioridad">Prioridad</Label>
                <select
                  id="prioridad"
                  v-model="filters.prioridad"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border px-3 py-2"
                >
                  <option value="">Todas las prioridades</option>
                  <option v-for="prioridad in prioridades" :key="prioridad.id" :value="prioridad.id">
                    {{ prioridad.nombre }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Botones de accion -->
            <div class="flex gap-2 mt-4">
              <Button @click="aplicarFiltros" class="bg-blue-600 hover:bg-blue-700 text-white">
                <Icon name="search" class="w-4 h-4 mr-2" />
                Buscar
              </Button>
              <Button @click="limpiarFiltros" variant="outline">
                <Icon name="x" class="w-4 h-4 mr-2" />
                Limpiar
              </Button>
              <Button @click="exportarExcel" class="bg-green-600 hover:bg-green-700 text-white">
                <Icon name="download" class="w-4 h-4 mr-2" />
                Exportar
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Tabla de incidencias -->
        <Card>
          <CardContent class="pt-6">
            <div v-if="loading" class="flex justify-center items-center py-12">
              <Icon name="loader" class="w-8 h-8 animate-spin text-blue-600" />
            </div>

            <div v-else-if="incidencias.data && incidencias.data.length > 0" class="overflow-x-auto">
              <table class="w-full">
                <thead class="bg-gray-50 border-b">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Descripcion</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Prioridad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Fecha</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr
                      v-for="(incidencia, index) in incidencias.data"
                    :key="incidencia.id"
                    @click="seleccionarIncidencia(incidencia)"
                    :class="[
                      'cursor-pointer transition',
                      selectedIncidencia?.id === incidencia.id
                        ? 'bg-blue-50 hover:bg-blue-100'
                        : 'hover:bg-gray-50'
                    ]"
                  >
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ incidencia.correlative }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{{ incidencia.descripcion_problema }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ incidencia.empleado?.usuario?.nombres.concat(' ', incidencia.empleado?.usuario?.apellidos) || 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getEstadoBadgeClass(incidencia.estado)" class="px-3 py-1 rounded-full text-xs font-semibold">
                        {{ incidencia.estado_relacion?.descripcion || 'N/A' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getPrioridadBadgeClass(incidencia.prioridad)" class="px-3 py-1 rounded-full text-xs font-semibold">
                        {{ getPrioridadNombre(incidencia.prioridad) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ formatDate(incidencia.fecha_incidencia) }}</td>
                  </tr>
                </tbody>
              </table>

              <!-- Paginacion -->
              <div class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                  Mostrando {{ incidencias.from }} a {{ incidencias.to }} de {{ incidencias.total }} incidencias
                </div>
                <div class="flex gap-2">
                  <Link
                    v-if="incidencias.prev_page_url"
                    :href="incidencias.prev_page_url"
                    class="px-3 py-1 border rounded hover:bg-gray-50"
                  >
                    Anterior
                  </Link>
                  <Link
                    v-if="incidencias.next_page_url"
                    :href="incidencias.next_page_url"
                    class="px-3 py-1 border rounded hover:bg-gray-50"
                  >
                    Siguiente
                  </Link>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-12">
              <Icon name="inbox" class="w-12 h-12 mx-auto text-gray-400 mb-4" />
              <p class="text-gray-600">No hay incidencias para mostrar</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Componente Atender -->
      <Atender
        ref="atenderRef"
        :cargos="cargos"

        @incidenciaAtendida="recargarIncidencias"
      />

      <!-- Toast notifications -->
      <ToastRoot
        v-model:open="toastOpen"
        :class="[
          'rounded-md p-4 shadow-lg fixed bottom-4 right-4 max-w-xs text-white',
          toastType === 'success' ? 'bg-green-500' : 'bg-red-500'
        ]"
      >
        <ToastTitle class="font-bold">{{ toastType === 'success' ? 'Éxito' : 'Error' }}</ToastTitle>
        <ToastDescription>{{ toastMessage }}</ToastDescription>
      </ToastRoot>

      <ToastViewport
        class="[--viewport-padding:_25px] fixed bottom-0 right-0 flex flex-col p-[var(--viewport-padding)] gap-[10px] w-[390px] max-w-[100vw]"
      />
    </ToastProvider>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Card from '@/components/ui/card/Card.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'
import Icon from '@/components/Icon.vue'
import { ToastProvider, ToastRoot, ToastTitle, ToastDescription, ToastViewport } from 'reka-ui'
import Atender from './Atender.vue'

const page = usePage()
const loading = ref(false)
const selectedIncidencia = ref(null)
const toastOpen = ref(false)
const toastMessage = ref('')
const toastType = ref('success')
const atenderRef = ref()

const filters = ref({
  search: '',
  estado: '',
  prioridad: ''
})

const incidencias = ref(page.props.incidencias || {})
const estados = ref(page.props.estados || [])
const prioridades = ref(page.props.prioridades || [])
const cargos = ref(page.props.cargos || [])

const showToast = (message, type = 'success') => {
  toastMessage.value = message
  toastType.value = type
  toastOpen.value = false
  setTimeout(() => {
    toastOpen.value = true
  }, 50)
}

const seleccionarIncidencia = (incidencia) => {
  selectedIncidencia.value = incidencia
}

const verIncidencia = () => {
  if (!selectedIncidencia.value) {
    showToast('No se seleccionó ninguna incidencia', 'error')
    return
  }
  router.visit(`/incidencias/${selectedIncidencia.value.id}`)
}

const atenderIncidencia = () => {
  if (!selectedIncidencia.value) {
    showToast('No se seleccionó ninguna incidencia', 'error')
    return
  }
  atenderRef.value.abrirModal(selectedIncidencia.value)
}

const recargarIncidencias = () => {
  selectedIncidencia.value = null
  router.visit('/incidencias')
}

const aplicarFiltros = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.search) params.append('search', filters.value.search)
    if (filters.value.estado) params.append('estado', filters.value.estado)
    if (filters.value.prioridad) params.append('prioridad', filters.value.prioridad)

    router.get('/incidencias', Object.fromEntries(params))
  } catch (error) {
    console.error('Error aplicando filtros:', error)
  } finally {
    loading.value = false
  }
}

const limpiarFiltros = () => {
  filters.value = {
    search: '',
    estado: '',
    prioridad: ''
  }
  router.get('/incidencias')
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-ES')
}

const getPrioridadNombre = (prioridad) => {
  const map = { 3: 'Alta', 2: 'Media', 1: 'Baja' }
  return map[prioridad] || 'N/A'
}

const getPrioridadBadgeClass = (prioridad) => {
  const map = {
    3: 'bg-red-100 text-red-800',
    2: 'bg-yellow-100 text-yellow-800',
    1: 'bg-green-100 text-green-800'
  }
  return map[prioridad] || 'bg-gray-100 text-gray-800'
}

const getEstadoBadgeClass = (estado) => {
  const map = {
    1: 'bg-blue-100 text-blue-800',
    2: 'bg-orange-100 text-orange-800',
    3: 'bg-yellow-100 text-yellow-800',
    4: 'bg-green-100 text-green-800',
    5: 'bg-red-100 text-red-800',
    6: 'bg-gray-100 text-gray-800'
  }
  return map[estado] || 'bg-gray-100 text-gray-800'
}

const exportarExcel = () => {
  const params = new URLSearchParams()
  if (filters.value.search) params.append('search', filters.value.search)
  if (filters.value.estado) params.append('estado', filters.value.estado)
  if (filters.value.prioridad) params.append('prioridad', filters.value.prioridad)

  const url = '/incidencias/exportar/excel' + (params.toString() ? '?' + params.toString() : '')
  window.location.href = url
}
</script>
