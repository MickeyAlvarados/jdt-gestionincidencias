<template>
    <div class="qr-generator">
        <div v-if="loading" class="flex items-center justify-center p-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <span class="ml-2 text-gray-600">Generando código QR...</span>
        </div>

        <div v-else-if="qrCode" class="text-center">
            <div class="mb-4">
                <img :src="qrCode" :alt="altText" class="mx-auto border-2 border-gray-300 rounded-lg shadow-lg" :class="sizeClass" />
            </div>

            <div v-if="showInfo" class="bg-gray-50 rounded-lg p-4 mb-4">
                <h4 class="font-semibold text-gray-800 mb-2">Información del Código QR</h4>
                <div class="text-sm text-gray-600 space-y-1">
                    <p><span class="font-medium">Token:</span> {{ token }}</p>
                    <p><span class="font-medium">Expira:</span> {{ formatExpiry(expiresAt) }}</p>
                    <p v-if="horario"><span class="font-medium">Horario:</span> {{ formatHorario(horario) }}</p>
                </div>
            </div>

            <div v-if="showDownload" class="mb-4">
                <button @click="downloadQr"
                    class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Descargar QR
                </button>
            </div>

            <div v-if="showRefresh" class="mb-4">
                <button @click="$emit('refresh')"
                    class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Regenerar
                </button>
            </div>
        </div>

        <div v-else class="text-center text-gray-500 p-8">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 21h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p>No hay código QR generado</p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    qrCode?: string;
    token?: string;
    expiresAt?: string;
    horario?: any;
    loading?: boolean;
    showInfo?: boolean;
    showDownload?: boolean;
    showRefresh?: boolean;
    size?: 'sm' | 'md' | 'lg';
    altText?: string;
}

const props = withDefaults(defineProps<Props>(), {
    qrCode: '',
    token: '',
    expiresAt: '',
    horario: null,
    loading: false,
    showInfo: true,
    showDownload: true,
    showRefresh: false,
    size: 'md',
    altText: 'Código QR'
});

const emit = defineEmits<{
    refresh: [];
}>();

// Computed para clases de tamaño
const sizeClass = computed(() => {
    const sizes = {
        sm: 'w-32 h-32',
        md: 'w-48 h-48',
        lg: 'w-64 h-64'
    };
    return sizes[props.size];
});

// Función para formatear fecha de expiración
const formatExpiry = (dateString: string) => {
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

// Función para formatear información del horario
const formatHorario = (horario: any) => {
    if (!horario) return '';
    const docente = horario.docente ? `${horario.docente.nombres} ${horario.docente.apellidos}` : '';
    const curso = horario.curso ? horario.curso.nombre : '';
    const dia = horario.dia_semana || '';
    const hora = horario.hora_inicio ? horario.hora_inicio.substring(0, 5) : '';

    return `${docente} - ${curso} (${dia} ${hora})`;
};

// Función para descargar el QR
const downloadQr = () => {
    if (!props.qrCode) return;

    const link = document.createElement('a');
    link.href = props.qrCode;
    link.download = `qr-${props.token || 'codigo'}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};
</script>

<style scoped>
.qr-generator {
    max-width: 100%;
}
</style>