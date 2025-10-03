<script setup lang="ts">
import { ref } from 'vue';
import {
    CollapsibleRoot,
    CollapsibleTrigger,
    CollapsibleContent
} from 'reka-ui';
import {
    ChevronRight,
    Home,
    Users,
    Settings,
    FileText,
    BarChart3,
    ShoppingCart,
    Package,
    CreditCard,
    Database,
} from 'lucide-vue-next';
import { type NavItem, type NavSubItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

// Utility function para combinar clases
const cn = (...classes: (string | undefined)[]) => twMerge(clsx(classes));
const { auth } = usePage().props
const page = usePage();
const staticItems: NavItem[] = auth.modules;
// Items estáticos con submenús
// const staticItems: NavItem[] = [
//     {
//         title: 'Dashboard',
//         href: '/dashboard',
//         icon: Home,
//     },
//     {
//         title: 'Usuarios',
//         href: '/users',
//         icon: Users,
//         subItems: [
//             { title: 'Lista de Usuarios', href: '/users' },
//             { title: 'Crear Usuario', href: '/users/create' },
//             { title: 'Roles y Permisos', href: '/users/roles' },
//             { title: 'Usuarios Activos', href: '/users/active' },
//         ]
//     },
//     {
//         title: 'Productos',
//         href: '/products',
//         icon: Package,
//         subItems: [
//             { title: 'Todos los Productos', href: '/products' },
//             { title: 'Crear Producto', href: '/products/create' },
//             { title: 'Categorías', href: '/products/categories' },
//             { title: 'Inventario', href: '/products/inventory' },
//             { title: 'Productos Agotados', href: '/products/out-of-stock' },
//         ]
//     },
//     {
//         title: 'Ventas',
//         href: '/sales',
//         icon: ShoppingCart,
//         subItems: [
//             { title: 'Todas las Ventas', href: '/sales' },
//             { title: 'Nueva Venta', href: '/sales/create' },
//             { title: 'Facturas', href: '/sales/invoices' },
//             { title: 'Devoluciones', href: '/sales/returns' },
//         ]
//     },
//     {
//         title: 'Reportes',
//         href: '/reports',
//         icon: BarChart3,
//         subItems: [
//             { title: 'Reporte de Ventas', href: '/reports/sales' },
//             { title: 'Reporte de Inventario', href: '/reports/inventory' },
//             { title: 'Reporte de Usuarios', href: '/reports/users' },
//             { title: 'Análisis Financiero', href: '/reports/financial' },
//         ]
//     },
//     {
//         title: 'Finanzas',
//         href: '/finance',
//         icon: CreditCard,
//         subItems: [
//             { title: 'Ingresos', href: '/finance/income' },
//             { title: 'Gastos', href: '/finance/expenses' },
//             { title: 'Presupuestos', href: '/finance/budgets' },
//             { title: 'Cuentas por Cobrar', href: '/finance/receivables' },
//             { title: 'Cuentas por Pagar', href: '/finance/payables' },
//         ]
//     },
//     {
//         title: 'Documentos',
//         href: '/documents',
//         icon: FileText,
//         subItems: [
//             { title: 'Todos los Documentos', href: '/documents' },
//             { title: 'Subir Documento', href: '/documents/upload' },
//             { title: 'Plantillas', href: '/documents/templates' },
//             { title: 'Archivados', href: '/documents/archived' },
//         ]
//     },
//     {
//         title: 'Sistema',
//         href: '/system',
//         icon: Database,
//         subItems: [
//             { title: 'Respaldo de Datos', href: '/system/backup' },
//             { title: 'Logs del Sistema', href: '/system/logs' },
//             { title: 'Importar Datos', href: '/system/import' },
//             { title: 'Exportar Datos', href: '/system/export' },
//             { title: 'Configuración DB', href: '/system/database' },
//         ]
//     },
//     {
//         title: 'Configuración',
//         href: '/settings',
//         icon: Settings,
//         subItems: [
//             { title: 'Configuración General', href: '/settings/general' },
//             { title: 'Perfil de Usuario', href: '/settings/profile' },
//             { title: 'Seguridad', href: '/settings/security' },
//             { title: 'Notificaciones', href: '/settings/notifications' },
//             { title: 'Integraciones', href: '/settings/integrations' },
//         ]
//     },
// ];

defineProps<{
    items?: NavItem[];
}>();



const openMenus = ref<Set<string>>(new Set());

const toggleMenu = (title: string) => {
    if (openMenus.value.has(title)) {
        openMenus.value.delete(title);
    } else {
        openMenus.value.add(title);
    }
};

const isMenuOpen = (title: string) => {
    return openMenus.value.has(title);
};

const isActive = (href: string) => {
    return page.url === href || page.url.startsWith(href + '/');
};

const hasActiveSubItem = (subItems: NavSubItem[]) => {
    return subItems?.some(subItem => isActive(subItem.href));
};

// Mapa de íconos para resolver nombres de string a componentes
const iconMap: Record<string, any> = {
    Settings,
    Package,
    Users,
    FileText,
    BarChart3,
    Home,
    ShoppingCart,
    CreditCard,
    Database,
    CheckCircle: Home, // Fallback para CheckCircle que no está importado
};

// Función para resolver íconos
const resolveIcon = (iconName: string) => {
    return iconMap[iconName] || Package; // Fallback a Package si no se encuentra
};

// Usar items estáticos si no se pasan items como prop
const menuItems = staticItems;
</script>

<template>
    <div class="px-2 py-0">
        <div class="space-y-1">
            <div v-for="item in menuItems" :key="item.title">
                <!-- Menú sin submenús -->
                <template v-if="!item.subItems || item.subItems.length === 0">
                    <Link
                        :href="route(item.href)"
                        :class="cn(
                            'flex items-center gap-2 rounded-md px-2 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground',
                            isActive(item.href)
                                ? 'bg-accent text-accent-foreground'
                                : 'text-sidebar-foreground/70'
                        )"
                    >
                        <component :is="resolveIcon(item.icon)" class="h-4 w-4" />
                        <span>{{ item.title }}</span>
                    </Link>
                </template>

                <!-- Menú con submenús usando Reka UI Collapsible -->
                <template v-else>
                    <CollapsibleRoot
                        :open="isMenuOpen(item.title)"
                        @update:open="() => toggleMenu(item.title)"
                    >
                        <CollapsibleTrigger
                            :class="cn(
                                'flex w-full items-center gap-2 rounded-md px-2 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground',
                                hasActiveSubItem(item.subItems)
                                    ? 'bg-accent text-accent-foreground'
                                    : 'text-sidebar-foreground/70'
                            )"
                        >
                            <component :is="resolveIcon(item.icon)" class="h-4 w-4" />
                            <span>{{ item.title }}</span>
                            <ChevronRight
                                :class="cn(
                                    'ml-auto h-4 w-4 transition-transform duration-200',
                                    isMenuOpen(item.title) ? 'rotate-90' : ''
                                )"
                            />
                        </CollapsibleTrigger>

                        <!-- Submenú desplegable -->
                        <CollapsibleContent class="overflow-hidden data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down">
                            <div class="ml-4 mt-1 space-y-1 border-l border-border pl-4">
                                <Link
                                    v-for="subItem in item.subItems"
                                    :key="subItem.title"
                                    :href="route(subItem.href)"
                                    :class="cn(
                                        'block rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-accent hover:text-accent-foreground',
                                        isActive(subItem.href)
                                            ? 'bg-accent text-accent-foreground font-medium'
                                            : 'text-sidebar-foreground/60'
                                    )"
                                >
                                    {{ subItem.title }}
                                </Link>
                            </div>
                        </CollapsibleContent>
                    </CollapsibleRoot>
                </template>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rotate-90 {
    transform: rotate(90deg);
}

/* Animaciones para el collapsible usando tw-animate-css */
@keyframes collapsible-down {
    from {
        height: 0;
    }
    to {
        height: var(--reka-collapsible-content-height);
    }
}

@keyframes collapsible-up {
    from {
        height: var(--reka-collapsible-content-height);
    }
    to {
        height: 0;
    }
}

.animate-collapsible-down {
    animation: collapsible-down 0.2s ease-out;
}

.animate-collapsible-up {
    animation: collapsible-up 0.2s ease-out;
}
</style>
