<script setup>
import { ref, computed, watch } from 'vue'
import api from '../../services/api'
import { User, Mail, Lock, Loader2, Check, AlertCircle } from 'lucide-vue-next'
import PasswordStrength from './PasswordStrength.vue'

const emit = defineEmits(['switch-to-login', 'success'])

const form = ref({ name: '', email: '', password: '', password_confirmation: '' })
const errors = ref({})
const loading = ref(false)
const apiError = ref('')

const debouncedPassword = ref('')
let pwdTimer = null
watch(() => form.value.password, (v) => {
  clearTimeout(pwdTimer)
  pwdTimer = setTimeout(() => { debouncedPassword.value = v }, 300)
})

const validations = computed(() => {
  const p = form.value.password
  return {
    length: p.length >= 8,
    upper: /[A-Z]/.test(p),
    digit: /\d/.test(p),
    match: form.value.password_confirmation && p === form.value.password_confirmation,
  }
})

const allValid = computed(() => {
  return form.value.name.trim().length > 0
    && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)
    && validations.value.length
    && validations.value.upper
    && validations.value.digit
    && validations.value.match
})

async function submit() {
  errors.value = {}
  apiError.value = ''
  if (!allValid.value) return
  loading.value = true
  try {
    await api.post('/register', form.value)
    emit('success', 'Conta criada com sucesso. Faça login para continuar.')
  } catch (e) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
    } else {
      apiError.value = e.response?.data?.message || 'Erro ao criar conta. Tente novamente.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-4">
    <div v-if="apiError" class="flex items-center gap-2 p-3 rounded-lg text-sm" style="background: var(--color-danger-light); color: var(--color-danger)">
      <AlertCircle :size="16" class="shrink-0" />
      {{ apiError }}
    </div>

    <div>
      <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">Nome completo *</label>
      <div class="relative">
        <User :size="16" class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-text-secondary); opacity: 0.5" />
        <input v-model="form.name" type="text" required class="input-field" style="padding-left: 2.5rem" placeholder="Seu nome" />
      </div>
      <p v-if="errors.name" class="text-xs mt-1" style="color: var(--color-danger)">{{ errors.name[0] }}</p>
    </div>

    <div>
      <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">E-mail corporativo *</label>
      <div class="relative">
        <Mail :size="16" class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-text-secondary); opacity: 0.5" />
        <input v-model="form.email" type="email" required class="input-field" style="padding-left: 2.5rem" placeholder="seu@empresa.com" />
      </div>
      <p v-if="errors.email" class="text-xs mt-1" style="color: var(--color-danger)">{{ errors.email[0] }}</p>
    </div>

    <div>
      <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">Senha *</label>
      <div class="relative">
        <Lock :size="16" class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-text-secondary); opacity: 0.5" />
        <input v-model="form.password" type="password" required class="input-field" style="padding-left: 2.5rem" placeholder="••••••••" />
      </div>
      <PasswordStrength :password="debouncedPassword" />
      <ul v-if="form.password" class="mt-2 space-y-0.5 text-[11px]">
        <li class="flex items-center gap-1.5" :style="{ color: validations.length ? 'var(--color-success)' : 'var(--color-text-secondary)' }">
          <Check v-if="validations.length" :size="12" /> <span v-else class="w-3 h-3 inline-block"></span>
          Mínimo 8 caracteres
        </li>
        <li class="flex items-center gap-1.5" :style="{ color: validations.upper ? 'var(--color-success)' : 'var(--color-text-secondary)' }">
          <Check v-if="validations.upper" :size="12" /> <span v-else class="w-3 h-3 inline-block"></span>
          1 letra maiúscula
        </li>
        <li class="flex items-center gap-1.5" :style="{ color: validations.digit ? 'var(--color-success)' : 'var(--color-text-secondary)' }">
          <Check v-if="validations.digit" :size="12" /> <span v-else class="w-3 h-3 inline-block"></span>
          1 número
        </li>
      </ul>
      <p v-if="errors.password" class="text-xs mt-1" style="color: var(--color-danger)">{{ errors.password[0] }}</p>
    </div>

    <div>
      <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">Confirmar senha *</label>
      <div class="relative">
        <Lock :size="16" class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-text-secondary); opacity: 0.5" />
        <input v-model="form.password_confirmation" type="password" required class="input-field" style="padding-left: 2.5rem" placeholder="••••••••" />
      </div>
      <p v-if="form.password_confirmation && !validations.match" class="text-xs mt-1" style="color: var(--color-danger)">As senhas não coincidem.</p>
    </div>

    <button type="submit" :disabled="loading || !allValid" class="btn-primary w-full !py-3 text-base">
      <Loader2 v-if="loading" :size="18" class="animate-spin" />
      <span v-else>Criar conta</span>
    </button>

    <p class="text-center text-xs" style="color: var(--color-text-secondary)">
      Já tem uma conta?
      <button type="button" @click="$emit('switch-to-login')" class="font-medium ml-1 hover:underline" style="color: var(--color-primary)">
        Entrar
      </button>
    </p>
  </form>
</template>
