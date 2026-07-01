<script setup>
import { ref, watch } from 'vue'
import BaseModal from '../ui/BaseModal.vue'
import { Loader2 } from 'lucide-vue-next'

const props = defineProps({
  show: { type: Boolean, default: false },
})
const emit = defineEmits(['close', 'created'])

const form = ref({ name: '', email: '', role: 'operator', password: '', password_confirmation: '' })
const errors = ref({})
const loading = ref(false)

watch(() => props.show, (v) => {
  if (v) {
    form.value = { name: '', email: '', role: 'operator', password: '', password_confirmation: '' }
    errors.value = {}
  }
})

async function submit() {
  errors.value = {}
  if (form.value.password !== form.value.password_confirmation) {
    errors.value = { password_confirmation: ['As senhas não coincidem.'] }
    return
  }
  loading.value = true
  try {
    emit('created', { ...form.value })
  } finally {
    loading.value = false
  }
}

function setErrors(e) {
  errors.value = e || {}
  loading.value = false
}

defineExpose({ setErrors })
</script>

<template>
  <BaseModal :show="show" title="Novo Usuário" @close="emit('close')">
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">Nome completo *</label>
        <input v-model="form.name" type="text" required class="input-field" />
        <p v-if="errors.name" class="text-xs mt-1" style="color: var(--color-danger)">{{ errors.name[0] }}</p>
      </div>

      <div>
        <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">E-mail *</label>
        <input v-model="form.email" type="email" required class="input-field" />
        <p v-if="errors.email" class="text-xs mt-1" style="color: var(--color-danger)">{{ errors.email[0] }}</p>
      </div>

      <div>
        <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">Perfil *</label>
        <select v-model="form.role" required class="input-field">
          <option value="operator">Operador</option>
          <option value="admin">Administrador</option>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">Senha temporária *</label>
          <input v-model="form.password" type="password" required minlength="8" class="input-field" />
          <p v-if="errors.password" class="text-xs mt-1" style="color: var(--color-danger)">{{ errors.password[0] }}</p>
        </div>
        <div>
          <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">Confirmar senha *</label>
          <input v-model="form.password_confirmation" type="password" required minlength="8" class="input-field" />
          <p v-if="errors.password_confirmation" class="text-xs mt-1" style="color: var(--color-danger)">{{ errors.password_confirmation[0] }}</p>
        </div>
      </div>
    </form>
    <template #footer>
      <button @click="emit('close')" class="btn-secondary">Cancelar</button>
      <button @click="submit" :disabled="loading" class="btn-primary">
        <Loader2 v-if="loading" :size="16" class="animate-spin" />
        {{ loading ? 'Salvando...' : 'Criar Usuário' }}
      </button>
    </template>
  </BaseModal>
</template>
