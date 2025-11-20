<template>
    <AppLayout title="Detalle de Incidencia">
        <template #header>
            <Heading>
                <Icon name="alert-circle" class="w-6 h-6" />
                Incidencia #{{ incidencia.id }}
            </Heading>
        </template>

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex gap-2 mb-6">
                <Link href="/incidencias"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                <Icon name="arrow-left" class="w-5 h-5 mr-2" />
                Volver
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Información de la Incidencia</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label class="text-gray-600 font-semibold">Descripción</Label>
                            <p class="text-gray-900 mt-1">{{ incidencia.descripcion_problema }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <Label class="text-gray-600 font-semibold">Usuario</Label>
                                <p class="text-gray-900 mt-1">{{ incidencia.empleado.usuario.nombres + ' ' +
                                    incidencia.empleado.usuario.apellidos || 'N/A' }}</p>
                            </div>
                            <div>
                                <Label class="text-gray-600 font-semibold">Categoría</Label>
                                <p class="text-gray-900 mt-1">{{ incidencia.categoria?.descripcion || 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <Label class="text-gray-600 font-semibold">Fecha de Creación</Label>
                                <p class="text-gray-900 mt-1">{{ formatDate(incidencia.fecha_incidencia) }}</p>
                            </div>
                            <div>
                                <Label class="text-gray-600 font-semibold">Estado</Label>
                                <span :class="getEstadoBadgeClass(incidencia.estado)"
                                    class="inline-block px-3 py-1 rounded-full text-xs font-semibold mt-1">
                                    {{ incidencia.estado_relacion?.descripcion || 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Resumen</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label class="text-gray-600 text-sm font-semibold">Prioridad Actual</Label>
                            <span :class="getPrioridadBadgeClass(incidencia.prioridad)"
                                class="inline-block px-3 py-1 rounded-full text-xs font-semibold mt-1">
                                {{ getPrioridadNombre(incidencia.prioridad) }}
                            </span>
                        </div>
                        <div>
                            <Label class="text-gray-600 text-sm font-semibold">Total de Detalles</Label>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ incidencia.detalles?.length || 0 }}</p>
                        </div>
                        <div v-if="incidencia.chat">
                            <Label class="text-gray-600 text-sm font-semibold">Chat Asociado</Label>
                            <p class="text-gray-900 mt-1">#{{ incidencia.chat.id }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sección de Cambio de Prioridad -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Actualizar Prioridad</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                        <div>
                            <Label for="prioridad" class="text-gray-600 font-semibold block mb-2">Nueva
                                Prioridad</Label>
                            <select id="prioridad" v-model.number="formulario.prioridad"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecciona una prioridad</option>
                                <option v-for="prioridad in prioridades" :key="prioridad.id" :value="prioridad.id">
                                    {{ prioridad.nombre }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <button @click="guardarPrioridad" :disabled="!formulario.prioridad || cargando"
                                class="w-full px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors font-semibold flex items-center justify-center gap-2">
                                <Icon v-if="!cargando" name="save" class="w-5 h-5" />
                                <span v-if="cargando">Guardando...</span>
                                <span v-else>Guardar</span>
                            </button>
                        </div>
                    </div>
                    <div v-if="mensaje" :class="[
                        'mt-4 p-3 rounded-lg text-sm font-semibold',
                        mensaje.tipo === 'success'
                            ? 'bg-green-100 text-green-800'
                            : 'bg-red-100 text-red-800'
                    ]">
                        {{ mensaje.texto }}
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <div class="flex justify-between items-center">
                        <CardTitle>Detalles de Atención</CardTitle>
                    </div>
                </CardHeader>
                <CardContent>
                    <div v-if="incidencia.detalles && incidencia.detalles.length > 0" class="space-y-4">
                        <div v-for="detalle in incidencia.detalles" :key="`${detalle.idincidencia}-${detalle.id}`"
                            class="p-4 border rounded-lg hover:bg-gray-50">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="text-sm text-gray-600">Iniciado: {{ formatDate(detalle.fecha_inicio) }}
                                    </p>
                                </div>
                                <span :class="getEstadoBadgeClass(detalle.estado_atencion.id)"
                                    class="px-3 py-1 rounded-full text-xs font-semibold">
                                    {{ getEstadoNombre(detalle.estado_atencion) }}
                                </span>
                            </div>
                            <p class="text-gray-700 mb-3">{{ detalle.comentarios }}</p>
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div>
                                    <Label class="text-gray-600 font-semibold">Técnico</Label>
                                    <p class="text-gray-900">{{ detalle.empleadoInformatica?.nombre || 'Sin asignar' }}
                                    </p>
                                </div>
                                <div>
                                    <Label class="text-gray-600 font-semibold">Área</Label>
                                    <p class="text-gray-900">{{ detalle.cargo?.descripcion || 'Sin asignar' }}</p>
                                </div>
                                <div>
                                    <Label class="text-gray-600 font-semibold">Fecha de Cierre</Label>
                                    <p class="text-gray-900">{{ detalle.fecha_cierre ? formatDate(detalle.fecha_cierre)
                                        :
                                        'Abierto' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8 text-gray-600">
                        <Icon name="inbox" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                        <p>No hay detalles de atención registrados</p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import Card from '@/components/ui/card/Card.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import Label from '@/components/ui/label/Label.vue'
import Icon from '@/components/Icon.vue'
import axios from 'axios'

const page = usePage()
const incidencia = ref(page.props.incidencia || {})
const prioridades = ref(page.props.prioridades || [])
const cargando = ref(false)
const mensaje = ref(null)

const formulario = ref({
    prioridad: incidencia.value.prioridad || ''
})

const guardarPrioridad = async () => {
    if (!formulario.value.prioridad) {
        mensaje.value = {
            tipo: 'error',
            texto: 'Por favor selecciona una prioridad'
        }
        return
    }

    cargando.value = true
    mensaje.value = null

    cargando.value = true;

    axios.put(`/incidencias/update-prioridad/${incidencia.value.id}`, {
        prioridad: formulario.value.prioridad
    }, {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
        .then(response => {
            // Si llega aquí es success
            incidencia.value.prioridad = formulario.value.prioridad;

            mensaje.value = {
                tipo: 'success',
                texto: response.data.message || 'Prioridad actualizada correctamente'
            };

            setTimeout(() => {
                mensaje.value = null;
            }, 3000);

            router.visit(`/incidencias/${incidencia.value.id}`);
        })
        .catch(error => {
            console.error("Error AXIOS:", error.response?.data || error.message);
            mensaje.value = {
                tipo: 'error',
                texto: error.response?.data?.message || 'Error al actualizar la prioridad'
            };
        })
        .finally(() => {
            cargando.value = false;
        });

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

const getEstadoNombre = (estado) => {
    return estado.descripcion || 'N/A'
}
</script>
