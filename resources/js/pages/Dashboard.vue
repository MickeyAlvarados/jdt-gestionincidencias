<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

interface Stats {
    totalIncidencias: number;
    incidenciasPendientes: number;
    incidenciasResueltas: number;
    incidenciasEnProceso: number;
    porcentajeResolucion: number;
    tiempoPromedioResolucion: number;
}

interface ChartData {
    incidenciasPorEstado: Array<{ estado: string; total: number }>;
    incidenciasPorPrioridad: Array<{ prioridad: string; total: number }>;
    incidenciasPorCategoria: Array<{ categoria: string; total: number }>;
    tendenciaIncidencias: Array<{ fecha: string; total: number }>;
    incidenciasPorArea: Array<{ area: string; total: number }>;
}

interface Incidencia {
    id: number;
    descripcion: string;
    usuario: string;
    estado: string;
    categoria: string;
    prioridad: number;
    fecha: string;
}

const props = defineProps<{
    stats: Stats;
    charts: ChartData;
    incidenciasRecientes: Incidencia[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// Configuración del gráfico de estados (Donut)
const estadosChartOptions = computed(() => ({
    chart: {
        type: 'donut',
        height: 300
    },
    labels: props.charts.incidenciasPorEstado.map(item => item.estado),
    colors: ['#3B82F6', '#F59E0B', '#10B981', '#EF4444', '#6B7280'],
    legend: {
        position: 'bottom'
    },
    responsive: [{
        breakpoint: 480,
        options: {
            chart: {
                width: 200
            },
            legend: {
                position: 'bottom'
            }
        }
    }]
}));

const estadosChartSeries = computed(() =>
    props.charts.incidenciasPorEstado.map(item => item.total)
);

// Configuración del gráfico de prioridades (Bar)
const prioridadesChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 300,
        toolbar: {
            show: false
        }
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '55%',
            distributed: true
        }
    },
    colors: ['#10B981', '#F59E0B', '#EF4444'],
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: props.charts.incidenciasPorPrioridad.map(item => item.prioridad),
    },
    legend: {
        show: false
    }
}));

const prioridadesChartSeries = computed(() => [{
    name: 'Incidencias',
    data: props.charts.incidenciasPorPrioridad.map(item => item.total)
}]);

// Configuración del gráfico de tendencia (Line)
const tendenciaChartOptions = computed(() => ({
    chart: {
        type: 'line',
        height: 300,
        toolbar: {
            show: false
        },
        zoom: {
            enabled: false
        }
    },
    stroke: {
        curve: 'smooth',
        width: 3
    },
    colors: ['#3B82F6'],
    xaxis: {
        categories: props.charts.tendenciaIncidencias.map(item => item.fecha),
    },
    yaxis: {
        title: {
            text: 'Cantidad'
        }
    },
    markers: {
        size: 5
    }
}));

const tendenciaChartSeries = computed(() => [{
    name: 'Incidencias',
    data: props.charts.tendenciaIncidencias.map(item => item.total)
}]);

// Configuración del gráfico de categorías (Bar horizontal)
const categoriasChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        height: 300,
        toolbar: {
            show: false
        }
    },
    plotOptions: {
        bar: {
            horizontal: true,
            distributed: true
        }
    },
    colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: props.charts.incidenciasPorCategoria.map(item => item.categoria),
    },
    legend: {
        show: false
    }
}));

const categoriasChartSeries = computed(() => [{
    name: 'Incidencias',
    data: props.charts.incidenciasPorCategoria.map(item => item.total)
}]);

const getPrioridadBadge = (prioridad: number) => {
    const map: Record<number, string> = {
        3: 'bg-red-100 text-red-800',
        2: 'bg-yellow-100 text-yellow-800',
        1: 'bg-green-100 text-green-800'
    };
    return map[prioridad] || 'bg-gray-100 text-gray-800';
};

const getPrioridadTexto = (prioridad: number) => {
    const map: Record<number, string> = {
        3: 'Alta',
        2: 'Media',
        1: 'Baja'
    };
    return map[prioridad] || 'N/A';
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6 overflow-x-auto">
            <!-- Tarjetas de estadísticas principales -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Incidencias -->
                <div class="relative overflow-hidden rounded-xl border bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Incidencias</p>
                            <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ stats.totalIncidencias }}</p>
                        </div>
                        <div class="rounded-full bg-blue-100 p-3 dark:bg-blue-900">
                            <svg class="h-6 w-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Pendientes -->
                <div class="relative overflow-hidden rounded-xl border bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pendientes</p>
                            <p class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">{{ stats.incidenciasPendientes }}</p>
                        </div>
                        <div class="rounded-full bg-orange-100 p-3 dark:bg-orange-900">
                            <svg class="h-6 w-6 text-orange-600 dark:text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- En Proceso -->
                <div class="relative overflow-hidden rounded-xl border bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">En Proceso</p>
                            <p class="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.incidenciasEnProceso }}</p>
                        </div>
                        <div class="rounded-full bg-yellow-100 p-3 dark:bg-yellow-900">
                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Resueltas -->
                <div class="relative overflow-hidden rounded-xl border bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Resueltas</p>
                            <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ stats.incidenciasResueltas }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ stats.porcentajeResolucion }}% del total</p>
                        </div>
                        <div class="rounded-full bg-green-100 p-3 dark:bg-green-900">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos principales -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Incidencias por Estado -->
                <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Incidencias por Estado</h3>
                    <VueApexCharts
                        type="donut"
                        :options="estadosChartOptions"
                        :series="estadosChartSeries"
                        height="300"
                    />
                </div>

                <!-- Incidencias por Prioridad -->
                <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Incidencias por Prioridad</h3>
                    <VueApexCharts
                        type="bar"
                        :options="prioridadesChartOptions"
                        :series="prioridadesChartSeries"
                        height="300"
                    />
                </div>
            </div>

            <!-- Tendencia y Categorías -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Tendencia (últimos 7 días) -->
                <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Tendencia (Últimos 7 días)</h3>
                    <VueApexCharts
                        type="line"
                        :options="tendenciaChartOptions"
                        :series="tendenciaChartSeries"
                        height="300"
                    />
                </div>

                <!-- Top 5 Categorías -->
                <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Top 5 Categorías</h3>
                    <VueApexCharts
                        type="bar"
                        :options="categoriasChartOptions"
                        :series="categoriasChartSeries"
                        height="300"
                    />
                </div>
            </div>

            <!-- Métricas adicionales e Incidencias recientes -->
            <div class="grid gap-4 md:grid-cols-3">
                <!-- Tiempo promedio de resolución -->
                <div class="rounded-xl border bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tiempo Promedio</h3>
                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-4xl font-bold text-blue-600 dark:text-blue-400">{{ stats.tiempoPromedioResolucion }}</p>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">días de resolución</p>
                </div>

                <!-- Incidencias Recientes -->
                <div class="md:col-span-2 rounded-xl border bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Incidencias Recientes</h3>
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <div
                            v-for="incidencia in incidenciasRecientes"
                            :key="incidencia.id"
                            class="flex items-start justify-between border-b pb-3 last:border-0 dark:border-gray-700"
                        >
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    #{{ incidencia.id }} - {{ incidencia.descripcion }}
                                </p>
                                <div class="mt-1 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                    <span>{{ incidencia.usuario }}</span>
                                    <span>•</span>
                                    <span>{{ incidencia.fecha }}</span>
                                </div>
                            </div>
                            <div class="ml-3 flex flex-col items-end gap-1">
                                <span :class="getPrioridadBadge(incidencia.prioridad)" class="px-2 py-1 rounded text-xs font-semibold">
                                    {{ getPrioridadTexto(incidencia.prioridad) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
