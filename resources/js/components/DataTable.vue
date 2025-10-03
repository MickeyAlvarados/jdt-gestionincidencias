<template>
    <div class="bg-white shadow rounded-lg">
      <!-- Search -->
      <div class="p-3 flex justify-between">
        <input
          v-model="search"
          @input="debouncedFetch"
          type="text"
          placeholder="Buscar..."
          class="border rounded px-2 py-1 text-sm"
        />
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                v-for="col in columns"
                :key="col.key"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"
              >
                {{ col.label }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="row in data"
              :key="row.id"
              @click="selectRow(row)"
              :class="[
                'cursor-pointer',
                selectedRow?.id === row.id ? 'bg-blue-100' : ''
              ]"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                class="px-6 py-4 whitespace-nowrap"
                v-html="resolveValue(row, col)"
              >
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="p-3 flex justify-end gap-2">
        <button
          :disabled="page === 1"
          @click="changePage(page - 1)"
          class="px-2 py-1 border rounded disabled:opacity-50"
        >
          Anterior
        </button>
        <span>Página {{ page }} de {{ totalPages }}</span>
        <button
          :disabled="page === totalPages"
          @click="changePage(page + 1)"
          class="px-2 py-1 border rounded disabled:opacity-50"
        >
          Siguiente
        </button>
      </div>
    </div>
  </template>

  <script setup lang="ts">
  import { ref, onMounted, watch } from 'vue'
  import axios from 'axios'

  interface Column {
    key: string
    label: string
    formatter?: (value: any) => string
  }

  interface Props {
    columns: Column[]
    fetchUrl: string
    perPage?: number
  }

  const props = defineProps<Props>()
  const emit = defineEmits(['rowSelected'])

  const data = ref<any[]>([])
  const page = ref(1)
  const search = ref('')
  const totalPages = ref(1)
  const perPage = ref(props.perPage || 10)
  const selectedRow = ref<any>(null)

  const fetchData = async () => {
    const res = await axios.get(props.fetchUrl, {
      params: {
        page: page.value,
        perPage: perPage.value,
        search: search.value
      }
    })
    data.value = res.data.data
    totalPages.value = res.data.last_page
  }

  let debounceTimer: number | undefined
  const debouncedFetch = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      fetchData()
    }, 500)
  }

  const changePage = (newPage: number) => {
    page.value = newPage
    fetchData()
  }

  const resolveValue = (obj: any, col: Column) => {
    const value = col.key.split('.').reduce((o, k) => (o ? o[k] : null), obj)
    return col.formatter ? col.formatter(value) : value
  }
  const resetRow = () => {
    selectedRow.value = null
  }


  const selectRow = (row: any) => {
    selectedRow.value = row
    emit('rowSelected', row) // Envía la fila seleccionada al padre
  }

  const reloadData = async () => {
    page.value = 1;  // opcional: resetear página si quieres
    await fetchData()
  }
  defineExpose({ reloadData, resetRow })
  onMounted(fetchData)
  watch([page, perPage], fetchData)
  </script>
