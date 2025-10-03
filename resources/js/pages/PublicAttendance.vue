<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const token = ref(page.props.token as string || '');
const tipoToken = ref(page.props.tipo_token as string || '');
const info = ref(page.props.info as any);
const isProcessing = ref(false);
const result = ref(null);
const error = ref('');

const markAttendance = async () => {
    if (!token.value) {
        error.value = 'Token no válido';
        return;
    }

    isProcessing.value = true;
    error.value = '';

    try {
        // Obtener ubicación GPS si está disponible
        let location = null;
        if (navigator.geolocation) {
            try {
                location = await getCurrentLocation();
            } catch (locError) {
                console.log('No se pudo obtener ubicación GPS:', locError);
            }
        }

        const payload: any = {
            token: token.value,
            latitud: location?.coords.latitude || null,
            longitud: location?.coords.longitude || null,
            precision_gps: location?.coords.accuracy || null
        };

        // Si es un token de docente, incluir el tipo
        if (tipoToken.value === 'docente' && info.value?.tipo) {
            payload.tipo = info.value.tipo;
        }

        const response = await axios.post('/qr/attendance', payload);

        if (response.data.success) {
            result.value = response.data;
        } else {
            error.value = response.data.message;
        }
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Error al registrar asistencia';
    } finally {
        isProcessing.value = false;
    }
};

const getCurrentLocation = (): Promise<GeolocationPosition> => {
    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        });
    });
};

const resetForm = () => {
    result.value = null;
    error.value = '';
};

// Función para formatear fechas
const formatDate = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('es-ES', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Registro de Asistencia
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ tipoToken === 'docente' && info?.tipo === 'salida' ? 'Registre su salida escaneando el código QR' : 'Confirme su asistencia escaneando el código QR' }}
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <!-- Error -->
                <div v-if="error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Resultado exitoso -->
                <div v-if="result" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">
                                ¡{{ result.tipo === 'salida' ? 'Salida' : 'Entrada' }} registrada!
                            </h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>Docente: {{ result.docente.nombres }} {{ result.docente.apellidos }}</p>
                                <p>Tipo: {{ result.tipo === 'salida' ? 'Salida' : 'Entrada' }}</p>
                                <p>Hora: {{ new Date().toLocaleTimeString() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario -->
                <form v-if="token && !result" @submit.prevent="markAttendance" class="space-y-6">
                    <!-- Información del QR -->
                    <div v-if="token && info" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                        <h4 class="font-semibold text-blue-800 mb-2">Información de la Asistencia</h4>
                        <div class="text-sm text-blue-700 space-y-1">
                            <p><span class="font-medium">Docente:</span> {{ info.docente ? info.docente.nombres + ' ' + info.docente.apellidos : 'No asignado' }}</p>
                            <p v-if="tipoToken === 'horario'"><span class="font-medium">Curso:</span> {{ info.curso ? info.curso.nombre_curso : 'No disponible' }}</p>
                            <p v-if="tipoToken === 'docente'"><span class="font-medium">Tipo:</span> {{ info.tipo === 'entrada' ? 'Entrada' : 'Salida' }}</p>
                            <p><span class="font-medium">Hora de Marcación:</span> {{ new Date().toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' }) }}</p>
                        </div>
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="isProcessing"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="isProcessing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isProcessing ? 'Registrando...' : (tipoToken === 'docente' && info?.tipo === 'salida' ? 'Registrar Salida' : 'Confirmar Asistencia') }}
                        </button>
                    </div>
                </form>

                <!-- Mensaje cuando no hay token -->
                <div v-if="!token" class="text-center text-gray-500 p-8">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 21h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>No se ha proporcionado un token válido</p>
                </div>
            </div>
        </div>
    </div>
</template>