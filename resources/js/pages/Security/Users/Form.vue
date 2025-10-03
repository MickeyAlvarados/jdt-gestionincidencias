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

interface Usuario {
    id: number
    nombres: string
    apellidos: string
    role_id: string
    email: string
    password?: string
    password_confirmation?: string
}
interface Role {
    id: number;
    name: string;
}

const open = ref(false)

const initData: Usuario = {
    id: -1,
    nombres: '',
    apellidos: '',
    role_id: '',
    email: '',
    password: '',
    password_confirmation: ''
}
const emit = defineEmits(['reloadData'])
const form = ref<Usuario>({ ...initData })
const editData = ref<Usuario | null>(null)
const roles = ref<Role[]>([])

const showPassword = ref(false)
const showConfirmPassword = ref(false)

const toastOpen = ref(false)
const toastMessage = ref('')
const toastType = ref<'success' | 'error'>('success')

const openDialog = (user?: Usuario) => {

    editData.value = user ?? null
    open.value = true
}

watch(open, (val) => {
    if (val) {
        form.value = editData.value ? { ...editData.value } : { ...initData }
    }
})

onMounted(() => {
    getRoles()
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

const showToast = (message: string, type: 'success' | 'error' = 'success') => {
    toastMessage.value = message
    toastType.value = type
    toastOpen.value = false
    setTimeout(() => {
        toastOpen.value = true
    }, 50)
}

const handleSubmit = () => {
    axios.post(route('users.store'), form.value)
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
                        {{ editData ? 'Editar Usuario' : 'Crear Usuario' }}
                    </DialogTitle>

                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1" for="nombres">Nombres</label>
                            <input id="nombres" v-model="form.nombres" type="text" required
                                class="w-full px-3 py-2 border rounded" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="apellidos">Apellidos</label>
                            <input id="apellidos" v-model="form.apellidos" type="text" required
                                class="w-full px-3 py-2 border rounded" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="role_id">Rol</label>
                            <select id="role_id" v-model="form.role_id" required
                                class="w-full px-3 py-2 border rounded bg-white">
                                <option value="">Seleccionar rol</option>
                                <option v-for="role in roles" :key="role.id" :value="role.id">
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1" for="email">Correo</label>
                            <input id="email" v-model="form.email" type="email" required
                                class="w-full px-3 py-2 border rounded" />
                        </div>

                        <div v-if="form.id == -1">
                            <label class="block text-sm font-medium mb-1" for="password">Contraseña</label>
                            <div class="relative">
                                <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                                    :required="!editData" class="w-full px-3 py-2 border rounded pr-10" />
                                <button type="button" @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274
                       4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478
                       0-8.268-2.943-9.542-7a9.957 9.957 0 012.104-3.592m3.886-2.555A9.953
                       9.953 0 0112 5c4.478 0 8.268 2.943
                       9.542 7a9.953 9.953 0 01-4.043 4.412M15
                       12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-if="form.id==-1">
                            <label class="block text-sm font-medium mb-1" for="password_confirmation">Confirmar
                                Contraseña</label>
                            <div class="relative">
                                <input id="password_confirmation" v-model="form.password_confirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'" :required="!editData"
                                    class="w-full px-3 py-2 border rounded pr-10" />
                                <button type="button" @click="showConfirmPassword = !showConfirmPassword"
                                    class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478
                       0 8.268 2.943 9.542 7-1.274
                       4.057-5.064 7-9.542 7-4.477
                       0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478
                       0-8.268-2.943-9.542-7a9.957 9.957 0 012.104-3.592m3.886-2.555A9.953
                       9.953 0 0112 5c4.478 0 8.268 2.943
                       9.542 7a9.953 9.953 0 01-4.043 4.412M15
                       12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
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
