<script setup>
import { ref, watch } from 'vue'
import BaseModal from '../ui/BaseModal.vue'
import { Loader2 } from 'lucide-vue-next'

const props = defineProps({
  show: { type: Boolean, default: false },
  user: { type: Object, default: null },
  isSelf: { type: Boolean, default: false },
})
const emit = defineEmits(['close', 'updated'])

const form = ref({ name: '', email: '', role: 'operator', is_active: true, password: '', password_confirmation: '' })
const errors = ref({})
const loading = ref(false)

watch(() => props.show, (v) => {
  if (v && props.user) {
    form.value = {
      name: props.user.name,
      email: props.user.email,
      role: props.user.role,
      is_active: props.user.is_active,
      password: '',
      password_confirmation: '',
    }
    errors.value = {}
  }
})

async function submit() {
  errors.value = {}
  if (form.value.password && form.value.password !== form.value.password_confirmation) {
    errors.value = { password_confirmation: ['As senhas não coincidem.'] }
    return
  }
  loading.value = true
  const payload = {
    name: form.value.name,
    email: form.value.email,
    role: form.value.role,
    is_active: form.value.is_active,
  }
  if (form.value.password) {
    payload.password = form.value.password
    payload.password_confirmation = form.value.password_confirmation
  }
  emit('updated', payload)
}

function setErrors(e) {
  errors.value = e || {}
  loading.value = false
}

defineExpose({ setErrors })
</script>

<template>
  <BaseModal :show="show" title="Editar Usuário" @close="emit('close')">
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

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">Perfil *</label>
          <select v-model="form.role" required class="input-field" :disabled="isSelf && user?.role === 'admin'">
            <option value="operator">Operador</option>
            <option value="admin">Administrador</option>
          </select>
          <p v-if="isSelf && user?.role === 'admin'" class="text-[11px] mt-1" style="color: var(--color-text-secondary)">Você não pode rebaixar o próprio perfil.</p>
          <p v-if="errors.role" class="text-xs mt-1" style="color: var(--color-danger)">{{ errors.role[0] }}</p>
        </div>
        <div>
          <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">Status *</label>
          <select v-model="form.is_active" required class="input-field" :disabled="isSelf">
            <option :value="true">Ativo</option>
            <option :value="false">Inativo</option>
          </select>
          <p v-if="isSelf" class="text-[11px] mt-1" style="color: var(--color-text-secondary)">Você não pode inativar a si mesmo.</p>
          <p v-if="errors.is_active" class="text-xs mt-1" style="color: var(--color-danger)">{{ errors.is_active[0] }}</p>
        </div>
      </div>

      <div class="pt-2 border-t" style="border-color: var(--color-border)">
        <p class="text-xs font-medium mb-2" style="color: var(--color-text-secondary)">Redefinir senha (opcional)</p>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <input v-model="form.password" type="password" minlength="8" placeholder="Nova senha" class="input-field" />
            <p v-if="errors.password" class="text-xs mt-1" style="color: var(--color-danger)">{{ errors.password[0] }}</p>
          </div>
          <div>
            <input v-model="form.password_confirmation" type="password" minlength="8" placeholder="Confirmar" class="input-field" />
            <p v-if="errors.password_confirmation" class="text-xs mt-1" style="color: var(--color-danger)">{{ errors.password_confirmation[0] }}</p>
          </div>
        </div>
      </div>
    </form>
    <template #footer>
      <button @click="emit('close')" class="btn-secondary">Cancelar</button>
      <button @click="submit" :disabled="loading" class="btn-primary">
        <Loader2 v-if="loading" :size="16" class="animate-spin" />
        {{ loading ? 'Salvando...' : 'Salvar' }}
      </button>
    </template>
  </BaseModal>
</template>
