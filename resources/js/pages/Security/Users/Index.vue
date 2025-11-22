<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import Form from './Form.vue';
import { ref, onMounted } from 'vue';
import { ToastProvider, ToastRoot, ToastTitle, ToastDescription, ToastViewport } from 'reka-ui';
import axios from 'axios';
const toastType = ref<'success' | 'error'>('success');

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/security/users',
    },
];

const formRef = ref();
interface Usuario {
    id: number;
    nombres: string;
    apellidos: string;
    codigo?: string;
    role_id: string;
    email: string;
    password?: string;
    password_confirmation?: string;
}
const selectedUser = ref<Usuario | null>(null)
const toastOpen = ref(false);
const toastMessage = ref('');
const dataTableRef = ref();
const showToast = (message: string, type: 'success' | 'error' = 'success') => {
    toastMessage.value = message
    toastType.value = type
    toastOpen.value = false
    setTimeout(() => {
        toastOpen.value = true
    }, 50)
}

const onRowSelected = (row: any) => {
    selectedUser.value = row

}
const reloadData = () => {
    if (dataTableRef.value) {
        dataTableRef.value.reloadData()
    }
}
defineExpose({ reloadData })
onMounted(() => {
});
const deleteUser = () => {
    if (!selectedUser.value) {
        showToast('No se seleccionó ningún usuario', 'error');
        return;
    }
    axios.delete(`/users/${selectedUser.value.id}`)
        .then(() => {
            reloadData()
            showToast('Usuario eliminado exitosamente', 'success');
        })
        .catch((error) => {
            console.error('Error al eliminar el usuario:', error);
            showToast('Error al eliminar el usuario', 'error');
        });
}
const editUser = () => {
    if (!selectedUser.value) {
        showToast('No se seleccionó ningún usuario', 'error');
        return;
    }
    formRef.value.openDialog(selectedUser.value);
}

const newUser = () => {
    formRef.value.openDialog();
};

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">

        <Head title="Password settings" />
        <ToastProvider>
            <div class="p-4">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-2xl font-semibold">Usuarios</h1>
                    <div class="flex gap-2">
                        <button @click="newUser"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-500 hover:bg-blue-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Nuevo
                        </button>
                        <button @click="editUser()"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-500 hover:bg-yellow-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536M9 11l6.586-6.586a2 2 0 012.828 0l1.172 1.172a2 2 0 010 2.828L13 15l-4 1 1-4z" />
                            </svg>
                            Editar
                        </button>
                        <button @click="deleteUser()"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-500 hover:bg-red-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 3h6a2 2 0 012 2v2H7V5a2 2 0 012-2z" />
                            </svg>
                            Eliminar
                        </button>
                    </div>
                </div>
                <div class="bg-white shadow rounded-lg">
                    <div class="overflow-x-auto">
                        <DataTable ref="dataTableRef" fetchUrl="users/list" :columns="[
                            { key: 'nombres', label: 'Nombres' },
                            { key: 'apellidos', label: 'Apellidos' },
                            { key: 'codigo', label: 'Código' },
                            { key: 'email', label: 'Correo' },
                            { key: 'role_name', label: 'Area' },

                        ]" @rowSelected="onRowSelected" />
                    </div>
                </div>
                <Form ref="formRef" @reloadData="reloadData" />
            </div>
            <ToastRoot v-model:open="toastOpen" :class="[
                'rounded-md p-4 shadow-lg fixed bottom-4 right-4 max-w-xs text-white',
                toastType === 'success' ? 'bg-green-500' : 'bg-red-500'
            ]">
                <ToastTitle class="font-bold">{{ toastType === 'success' ? 'Éxito' : 'Error' }}</ToastTitle>
                <ToastDescription>{{ toastMessage }}</ToastDescription>
            </ToastRoot>

            <ToastViewport
                class="[--viewport-padding:_25px] fixed bottom-0 right-0 flex flex-col p-[var(--viewport-padding)] gap-[10px] w-[390px] max-w-[100vw]" />
        </ToastProvider>
    </AppLayout>
</template>
