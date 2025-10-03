<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Icon } from '@iconify/vue'
import { ref, onMounted, watch } from 'vue';
import {
    ToastProvider,
    ToastRoot,
    ToastTitle,
    ToastDescription,
    ToastViewport,
    TreeRoot,
    TreeItem
} from 'reka-ui';
import axios from 'axios';
import { route } from 'ziggy-js';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/security/users',
    },
];

interface Role {
    id: number;
    name: string;
}

interface form {
    id: number
    name: string
    modulo_id: number,
    role_id: number
}

interface permiso {
    id: number
    name: string
    title: string
    isChecked: boolean
    module_id?: number
    children?: permiso[]
}

const initData: form = {
    id: 0,
    name: '',
    modulo_id: 0,
    role_id: 0
}

const toastType = ref<'success' | 'error'>('success')
const toastOpen = ref(false)
const toastMessage = ref('')
const form = ref<form>({ ...initData })
const roles = ref<Role[]>([])
const permissions = ref<permiso[]>([])

const items = ref([])

onMounted(() => {
    getRoles()
    getPermissions()
})

const getRoles = () => {
    axios.get(route('roles.getRoles'))
        .then(res => {
            roles.value = res.data
        })
        .catch(err => {
            console.error(err)
        })
}

const getPermissions = () => {
    form.value.role_id = form.value.id
    axios.get(route('permissions.listRole'), {
        params: form.value
    })
        .then(res => {
            console.log(res.data)
            items.value = res.data
        })
        .catch(err => {
            console.error(err)
        })
}

const isLowestLevel = (item: any) => {
    return !item.children || item.children.length === 0
}

const handleCheckboxChange = (item: any, event: Event) => {
    const target = event.target as HTMLInputElement
    item.isChecked = target.checked
}

const savePermissions = () => {
    axios.post(route('permissions.save'),{
        role_id: form.value.id,
        permission: items.value
    })
        .then(res => {
            showToast('Permisos guardados correctamente', 'success')
            window.location.reload();
        })
        .catch(err => {
            if(err.response.status == 422){
                showToast(err.response.data.error, 'error')
            }else{
                showToast('Error al guardar los permisos', 'error')
            }
            console.error(err)
        })
}
const showToast = (message: string, type: 'success' | 'error' = 'success') => {
    toastMessage.value = message
    toastType.value = type
    toastOpen.value = false
    setTimeout(() => {
        toastOpen.value = true
    }, 50)
}

watch(
    () => form.value.id,
    () => {
        getPermissions()
    }
)
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Password settings" />
        <ToastProvider>
            <div class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-2xl font-semibold">Permisos</h1>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white shadow rounded-lg p-3 flex flex-col gap-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <select id="role_id" v-model="form.id" required
                                    class="w-full px-3 py-2 border rounded bg-white">
                                    <option value="0">Seleccionar rol</option>
                                    <option v-for="role in roles" :key="role.id" :value="role.id">
                                        {{ role.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <button
                                    @click="savePermissions()"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-500 hover:bg-blue-600">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Guardar
                                </button>
                            </div>
                        </div>
                        <div class="mt-4">
                            <TreeRoot v-slot="{ flattenItems }"
                                class="list-none select-none w-64 bg-white text-stone-700 rounded-lg border shadow-sm p-2 text-sm font-medium"
                                :items="items"
                                :get-key="(item) => item.title"
                                :default-expanded="['src', 'components', 'UI']">
                                <TreeItem v-for="item in flattenItems" v-slot="{ isExpanded }" :key="item._id"
                                    :style="{ 'padding-left': `${item.level - 0.5}rem` }" v-bind="item.bind"
                                    class="flex items-center gap-2 py-1 px-2 my-0.5 rounded outline-none focus:ring-grass8 focus:ring-2 data-[selected]:bg-grass4">

                                    <!-- Solo mostrar checkbox en el nivel más bajo -->
                                    <input
                                        v-if="isLowestLevel(item.value)"
                                        type="checkbox"
                                        :checked="item.value.isChecked"
                                        @change="handleCheckboxChange(item.value, $event)"
                                        class="w-4 h-4 accent-green-600 cursor-pointer"
                                    />

                                    <!-- Icono para elementos con children -->
                                    <Icon
                                        v-else-if="item.value.children && item.value.children.length > 0"
                                        :icon="isExpanded ? 'mdi:chevron-down' : 'mdi:chevron-right'"
                                        class="w-4 h-4 text-gray-500"
                                    />

                                    <div>{{ item.value.title }}</div>
                                </TreeItem>
                            </TreeRoot>
                        </div>
                    </div>
                </div>
            </div>

            <ToastRoot v-model:open="toastOpen" :class="[
                'rounded-md p-4 shadow-lg fixed bottom-4 right-4 max-w-xs text-white',
                toastType === 'success' ? 'bg-green-500' : 'bg-red-500'
            ]">
                <ToastTitle class="font-bold">
                    {{ toastType === 'success' ? 'Éxito' : 'Error' }}
                </ToastTitle>
                <ToastDescription>{{ toastMessage }}</ToastDescription>
            </ToastRoot>

            <ToastViewport
                class="[--viewport-padding:_25px] fixed bottom-0 right-0 flex flex-col p-[var(--viewport-padding)] gap-[10px] w-[390px] max-w-[100vv]" />
        </ToastProvider>
    </AppLayout>
</template>
