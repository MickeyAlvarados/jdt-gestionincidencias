<template>
  <AppLayout title="Editar Incidencia">
    <template #header>
      <Heading>
        <Icon name="edit" class="w-6 h-6" />
        Editar Incidencia #{{ incidencia.id }}
      </Heading>
    </template>

    <div class="px-4 py-6 sm:px-6 lg:px-8">
      <Card class="max-w-2xl">
        <CardHeader>
          <CardTitle>Formulario de Edicion</CardTitle>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="actualizarIncidencia" class="space-y-6">
            <div>
              <Label for="descripcion">Descripcion del Problema *</Label>
              <textarea
                id="descripcion"
                v-model="form.descripcion_problema"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border px-3 py-2"
                rows="4"
                placeholder="Describe el problema detalladamente..."
                required
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="categoria">Categoria</Label>
                <select
                  id="categoria"
                  v-model="form.idcategoria"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border px-3 py-2"
                >
                  <option value="">Seleccionar categoria</option>
                  <option v-for="cat in categorias" :key="cat.id" :value="cat.id">
                    {{ cat.nombre }}
                  </option>
                </select>
              </div>
              <div>
                <Label for="empleado">Usuario *</Label>
                <select
                  id="empleado"
                  v-model="form.idempleado"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border px-3 py-2"
                  required
                >
                  <option value="">Seleccionar usuario</option>
                  <option v-for="emp in empleados" :key="emp.id" :value="emp.id">
                    {{ emp.nombre }}
                  </option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <Label for="prioridad">Prioridad *</Label>
                <select
                  id="prioridad"
                  v-model="form.prioridad"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border px-3 py-2"
                  required
                >
                  <option value="">Seleccionar prioridad</option>
                  <option v-for="p in prioridades" :key="p.id" :value="p.id">
                    {{ p.nombre }}
                  </option>
                </select>
              </div>
              <div>
                <Label for="estado">Estado *</Label>
                <select
                  id="estado"
                  v-model="form.estado"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 border px-3 py-2"
                  required
                >
                  <option value="">Seleccionar estado</option>
                  <option v-for="est in estados" :key="est.id" :value="est.id">
                    {{ est.nombre }}
                  </option>
                </select>
              </div>
            </div>

            <div class="flex gap-4">
              <Button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white" :disabled="loading">
                <Icon v-if="loading" name="loader" class="w-4 h-4 mr-2 animate-spin" />
                <Icon v-else name="save" class="w-4 h-4 mr-2" />
                {{ loading ? 'Actualizando...' : 'Actualizar' }}
              </Button>
              <Link :href="`/incidencias/${incidencia.id}`" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                Cancelar
              </Link>
            </div>
          </form>
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
import Button from '@/components/ui/button/Button.vue'
import Label from '@/components/ui/label/Label.vue'
import Icon from '@/components/Icon.vue'

const page = usePage()
const incidencia = ref(page.props.incidencia || {})
const loading = ref(false)

const form = ref({
  descripcion_problema: incidencia.value.descripcion_problema || '',
  idcategoria: incidencia.value.idcategoria || '',
  idempleado: incidencia.value.idempleado || '',
  prioridad: incidencia.value.prioridad || '',
  estado: incidencia.value.estado || ''
})

const categorias = ref(page.props.categorias || [])
const empleados = ref(page.props.empleados || [])
const estados = ref(page.props.estados || [])
const prioridades = ref(page.props.prioridades || [])

const actualizarIncidencia = async () => {
  if (!form.value.descripcion_problema || !form.value.idempleado || !form.value.prioridad || !form.value.estado) {
    alert('Por favor completa todos los campos requeridos')
    return
  }

  loading.value = true
  try {
    router.put(`/incidencias/${incidencia.value.id}`, form.value)
  } catch (error) {
    console.error('Error actualizando incidencia:', error)
    alert('Error al actualizar la incidencia')
    loading.value = false
  }
}
</script>
