<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import axios from 'axios';
import {
    DialogRoot,
    DialogPortal,
    DialogOverlay,
    DialogContent,
    DialogTitle,
    DialogClose,
    ToastProvider,
    ToastRoot,
    ToastTitle,
    ToastDescription,
    ToastViewport
} from 'reka-ui'

interface Role {
    id: number
    name: string
}
import { route } from 'ziggy-js';
const open = ref(false)

const initData: Role = {
    id: -1,
    name: '',
}
const emit = defineEmits(['reloadData'])
const form = ref<Role>({ ...initData })
const editData = ref<Role | null>(null)

const toastOpen = ref(false)
const toastMessage = ref('')
const toastType = ref<'success' | 'error'>('success')

const openDialog = (user?: Role) => {
    editData.value = user ?? null
    open.value = true
}

watch(open, (val) => {
    if (val) {
        form.value = editData.value ? { ...editData.value } : { ...initData }
    }
})

onMounted(() => {
})



const showToast = (message: string, type: 'success' | 'error' = 'success') => {
    toastMessage.value = message
    toastType.value = type
    toastOpen.value = false
    setTimeout(() => {
        toastOpen.value = true
    }, 50)
}

const handleSubmit = () => {
    axios.post(route('roles.store'), form.value)
        .then(() => {
            open.value = false
            showToast('Usuario guardado correctamente', 'success')
            emit('reloadData')
        })
        .catch((error) => {
            console.error('Error al guardar usuario:', error)
            if (error.response?.data?.errors) {
                const errores = error.response.data.errors
                const primerCampo = Object.keys(errores)[0]
                const primerMensaje = errores[primerCampo][0]
                showToast(primerMensaje, 'error')
            } else if (error.response?.data?.message) {
                showToast(error.response.data.message, 'error')
            } else {
                showToast('Hubo un problema al guardar el usuario', 'error')
            }
        })
}
defineExpose({
    openDialog,
})
</script>

<template>
    <ToastProvider>
        <DialogRoot v-model:open="open" modal>
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 bg-black/50" />
                <DialogContent class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2
                       bg-white p-6 rounded-md w-full max-w-md">
                    <DialogTitle class="text-xl font-semibold mb-4">
                        {{ editData ? 'Editar Rol' : 'Crear Rol' }}
                    </DialogTitle>

                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1" for="name">Nombre</label>
                            <input id="name" v-model="form.name" type="text" required
                                class="w-full px-3 py-2 border rounded" />
                        </div>
                        <div class="flex justify-end space-x-2 mt-4">
                            <DialogClose asChild>
                                <button type="button" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
                            </DialogClose>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
                                Guardar
                            </button>
                        </div>
                    </form>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>

        <!-- Toast -->
        <ToastRoot v-model:open="toastOpen" :class="[
            'rounded-md p-4 shadow-lg fixed bottom-4 right-4 max-w-xs text-white',
            toastType === 'success' ? 'bg-green-500' : 'bg-red-500'
        ]">
            <ToastTitle class="font-bold">{{ toastType === 'success' ? 'Éxito' : 'Error' }}</ToastTitle>
            <ToastDescription>{{ toastMessage }}</ToastDescription>
        </ToastRoot>
        <ToastViewport class="fixed bottom-0 right-0 p-4 flex flex-col gap-2 w-96 max-w-full m-0 list-none z-[100]" />
    </ToastProvider>
</template>
