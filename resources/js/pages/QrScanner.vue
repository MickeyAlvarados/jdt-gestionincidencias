<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Escanear QR - Asistencia" />

        <div class="p-6">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="px-6 py-4 bg-blue-500 text-white">
                        <h1 class="text-2xl font-bold">Escanear Código QR</h1>
                        <p class="text-blue-100 mt-1">Confirma tu asistencia escaneando el código QR generado</p>
                    </div>

                    <div class="p-6">
                        <!-- Selector de dispositivo de cámara -->
                        <div v-if="!scanning" class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Seleccionar Cámara
                            </label>
                            <select v-model="selectedDevice" @change="changeCamera"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Cámara predeterminada</option>
                                <option v-for="device in videoDevices" :key="device.deviceId" :value="device.deviceId">
                                    {{ device.label || `Cámara ${device.deviceId.slice(0, 8)}` }}
                                </option>
                            </select>
                        </div>

                        <!-- Área de escaneo -->
                        <div class="mb-6">
                            <div v-if="scanning" class="relative">
                                <video ref="videoElement" autoplay playsinline muted
                                    class="w-full max-w-md mx-auto border-2 border-blue-300 rounded-lg"></video>

                                <!-- Overlay con instrucciones -->
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <div class="text-center text-white bg-black bg-opacity-50 rounded-lg p-4">
                                        <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 21h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-sm font-medium">Enfoca el código QR</p>
                                    </div>
                                </div>

                                <!-- Botón para detener escaneo -->
                                <div class="mt-4 text-center">
                                    <button @click="stopScanning"
                                        class="inline-flex items-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6m-6 4h6m-6 4h6"></path>
                                        </svg>
                                        Detener Escaneo
                                    </button>
                                </div>
                            </div>

                            <div v-else class="text-center py-12">
                                <svg class="w-24 h-24 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 21h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Escáner QR Inactivo</h3>
                                <p class="text-gray-500 mb-6">Haz clic en el botón para comenzar a escanear códigos QR</p>
                                <button @click="startScanning"
                                    class="inline-flex items-center px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 21h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Iniciar Escaneo
                                </button>
                            </div>
                        </div>

                        <!-- Información del QR escaneado -->
                        <div v-if="scanResult" class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h4 class="font-semibold text-gray-800 mb-3">Código QR Detectado</h4>

                            <div v-if="validating" class="text-center py-4">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-2"></div>
                                <p class="text-gray-600">Validando código QR...</p>
                            </div>

                            <div v-else-if="qrData">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-600"><span class="font-medium">Estado:</span>
                                            <span :class="qrData.valid ? 'text-green-600' : 'text-red-600'">
                                                {{ qrData.valid ? 'Válido' : 'Inválido' }}
                                            </span>
                                        </p>
                                        <p v-if="qrData.horario" class="text-sm text-gray-600">
                                            <span class="font-medium">Horario:</span> {{ formatHorario(qrData.horario) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p v-if="qrData.expires_at" class="text-sm text-gray-600">
                                            <span class="font-medium">Expira:</span> {{ formatDate(qrData.expires_at) }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            <span class="font-medium">Token:</span> {{ qrData.token }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Botón de confirmación -->
                                <div v-if="qrData.valid" class="text-center">
                                    <button @click="confirmAttendance"
                                        :disabled="confirming"
                                        class="inline-flex items-center px-6 py-3 bg-green-500 text-white rounded-lg hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        <svg v-if="confirming" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ confirming ? 'Confirmando...' : 'Confirmar Asistencia' }}
                                    </button>
                                </div>

                                <div v-else class="text-center text-red-600">
                                    <p class="font-medium">{{ qrData.message }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Historial de asistencias recientes -->
                        <div v-if="recentAttendances.length > 0" class="border-t pt-6">
                            <h4 class="font-semibold text-gray-800 mb-3">Asistencias Recientes</h4>
                            <div class="space-y-2">
                                <div v-for="attendance in recentAttendances" :key="attendance.id"
                                    class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-800">{{ formatHorario(attendance.horario) }}</p>
                                        <p class="text-sm text-gray-600">{{ formatDate(attendance.fecha_asistencia) }} {{ attendance.hora_llegada }}</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full"
                                        :class="attendance.id_tipo_asistencia === 1 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                        {{ attendance.tipo_asistencia?.nombre_tipo || 'Registrado' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Asistencia',
        href: '/qr-scanner',
    },
];

// Refs
const videoElement = ref<HTMLVideoElement>();
const scanning = ref(false);
const selectedDevice = ref('');
const videoDevices = ref<MediaDeviceInfo[]>([]);
const scanResult = ref('');
const validating = ref(false);
const confirming = ref(false);
const qrData = ref<any>(null);
const recentAttendances = ref<any[]>([]);

// Instancia del escáner QR
let qrScanner: any = null;

onMounted(async () => {
    await loadRecentAttendances();
    await getVideoDevices();
});

onUnmounted(() => {
    stopScanning();
});

// Obtener dispositivos de video disponibles
const getVideoDevices = async () => {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        videoDevices.value = devices.filter(device => device.kind === 'videoinput');
    } catch (error) {
        console.error('Error obteniendo dispositivos de video:', error);
    }
};

// Cambiar cámara
const changeCamera = () => {
    if (scanning.value) {
        stopScanning();
        setTimeout(() => startScanning(), 500);
    }
};

// Iniciar escaneo
const startScanning = async () => {
    try {
        const constraints: MediaStreamConstraints = {
            video: {
                deviceId: selectedDevice.value ? { exact: selectedDevice.value } : undefined,
                facingMode: 'environment' // Cámara trasera por defecto
            }
        };

        const stream = await navigator.mediaDevices.getUserMedia(constraints);

        if (videoElement.value) {
            videoElement.value.srcObject = stream;
            scanning.value = true;

            // Aquí iría la lógica del escáner QR
            // Por simplicidad, simularemos el escaneo
            simulateQrScan();
        }
    } catch (error) {
        console.error('Error iniciando escaneo:', error);
        alert('Error al acceder a la cámara. Asegúrate de dar permisos.');
    }
};

// Detener escaneo
const stopScanning = () => {
    if (videoElement.value && videoElement.value.srcObject) {
        const stream = videoElement.value.srcObject as MediaStream;
        stream.getTracks().forEach(track => track.stop());
        videoElement.value.srcObject = null;
    }
    scanning.value = false;
    scanResult.value = '';
    qrData.value = null;
};

// Simular escaneo QR (en producción usar una librería como jsQR)
const simulateQrScan = () => {
    // Simulación - en producción reemplazar con lógica real de escaneo
    setTimeout(() => {
        // Simular datos de QR escaneado
        const mockQrData = JSON.stringify({
            token: 'abc123def456',
            id_horario: 1,
            timestamp: Date.now(),
            expires_at: Date.now() + 600000 // 10 minutos
        });

        processScannedData(mockQrData);
    }, 3000);
};

// Procesar datos escaneados
const processScannedData = async (data: string) => {
    scanResult.value = data;
    validating.value = true;

    try {
        const response = await axios.post('/qr/validate', {
            qr_data: data
        });

        qrData.value = response.data;
    } catch (error: any) {
        qrData.value = {
            valid: false,
            message: error.response?.data?.message || 'Error al validar el código QR'
        };
    } finally {
        validating.value = false;
    }
};

// Confirmar asistencia
const confirmAttendance = async () => {
    if (!qrData.value || !qrData.value.valid) return;

    confirming.value = true;

    try {
        // Obtener ubicación GPS si está disponible
        let location = null;
        if (navigator.geolocation) {
            location = await getCurrentLocation();
        }

        const response = await axios.post('/qr/confirm', {
            token: qrData.value.qr_token?.token,
            id_docente: 1, // En producción obtener del usuario autenticado
            latitud: location?.coords.latitude,
            longitud: location?.coords.longitude,
            precision_gps: location?.coords.accuracy
        });

        if (response.data.success) {
            alert('Asistencia confirmada exitosamente');
            await loadRecentAttendances();
            stopScanning();
        } else {
            alert(response.data.message || 'Error al confirmar asistencia');
        }
    } catch (error: any) {
        alert(error.response?.data?.message || 'Error al confirmar asistencia');
    } finally {
        confirming.value = false;
    }
};

// Obtener ubicación actual
const getCurrentLocation = (): Promise<GeolocationPosition> => {
    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        });
    });
};

// Cargar asistencias recientes
const loadRecentAttendances = async () => {
    try {
        // En producción, cargar asistencias del docente actual
        recentAttendances.value = [];
    } catch (error) {
        console.error('Error cargando asistencias recientes:', error);
    }
};

// Funciones de formateo
const formatHorario = (horario: any) => {
    if (!horario) return '';
    const docente = horario.docente ? `${horario.docente.nombres} ${horario.docente.apellidos}` : '';
    const curso = horario.curso ? horario.curso.nombre : '';
    const dia = horario.dia_semana || '';
    const hora = horario.hora_inicio ? horario.hora_inicio.substring(0, 5) : '';
    return `${docente} - ${curso} (${dia} ${hora})`;
};

const formatDate = (dateString: string) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('es-ES');
};
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>