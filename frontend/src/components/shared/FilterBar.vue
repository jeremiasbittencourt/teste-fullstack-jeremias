<script setup>
import { ref, onMounted } from 'vue'
import { Search, X } from 'lucide-vue-next'
import api from '../../services/api'

const props = defineProps({
  modelValue: { type: Object, required: true },
  showApplicationFilter: { type: Boolean, default: true },
  showStatusFilter: { type: Boolean, default: true },
  showDateFilter: { type: Boolean, default: true },
  showDepartmentFilter: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'filter'])
const applications = ref([])

const statusOptions = [
  { value: '', label: 'Todos os Status' },
  { value: 'open', label: 'Aberto' },
  { value: 'in_progress', label: 'Em Andamento' },
  { value: 'resolved', label: 'Resolvido' },
  { value: 'blocked', label: 'Bloqueado' },
  { value: 'form_modified', label: 'Formulário Modificado' },
  { value: 'pending_access_creation', label: 'Criação de Acesso' },
]

function update(key, value) {
  emit('update:modelValue', { ...props.modelValue, [key]: value })
  emit('filter')
}

function reset() {
  const cleared = {}
  Object.keys(props.modelValue).forEach(k => cleared[k] = '')
  emit('update:modelValue', cleared)
  emit('filter')
}

const hasFilters = () => Object.values(props.modelValue).some(v => v !== '')

onMounted(async () => {
  try {
    const { data } = await api.get('/applications', { params: { active_only: true } })
    applications.value = data
  } catch {}
})
</script>

<template>
  <div class="card p-4 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="relative">
        <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-text-secondary); opacity: 0.5" />
        <input
          type="text"
          :value="modelValue.search"
          @input="update('search', $event.target.value)"
          placeholder="Buscar por nome ou chamado..."
          class="input-field text-sm" style="padding-left: 2.25rem"
        />
      </div>

      <select v-if="showStatusFilter" :value="modelValue.status" @change="update('status', $event.target.value)" class="input-field text-sm">
        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>

      <select v-if="showApplicationFilter" :value="modelValue.application_id" @change="update('application_id', $event.target.value)" class="input-field text-sm">
        <option value="">Todas as Aplicações</option>
        <option v-for="app in applications" :key="app.id" :value="app.id">{{ app.name }}</option>
      </select>

      <input v-if="showDepartmentFilter" type="text" :value="modelValue.department" @input="update('department', $event.target.value)" placeholder="Área/Diretoria..." class="input-field text-sm" />

      <input v-if="showDateFilter" type="date" :value="modelValue.date_from" @change="update('date_from', $event.target.value)" class="input-field text-sm" />

      <input v-if="showDateFilter" type="date" :value="modelValue.date_to" @change="update('date_to', $event.target.value)" class="input-field text-sm" />

      <div v-if="hasFilters()" class="flex items-center">
        <button @click="reset" class="btn-ghost btn-sm gap-1">
          <X :size="14" />
          Limpar filtros
        </button>
      </div>
    </div>
  </div>
</template>
