<script setup>
import { ref } from 'vue'
import { useAuth } from '../../composables/useAuth'
import { Mail, Lock, Loader2 } from 'lucide-vue-next'

const props = defineProps({
  successMessage: { type: String, default: '' },
})
defineEmits(['switch-to-register'])

const { handleLogin, loading, error } = useAuth()
const email = ref('')
const password = ref('')
</script>

<template>
  <form @submit.prevent="handleLogin(email, password)" class="space-y-5">
    <div v-if="successMessage" class="flex items-start gap-2 p-3 rounded-lg text-sm" style="background: var(--color-success-light); color: var(--color-success)">
      <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
      {{ successMessage }}
    </div>

    <div v-if="error" class="flex items-center gap-2 p-3 rounded-lg text-sm" style="background: var(--color-danger-light); color: var(--color-danger)">
      <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
      {{ error }}
    </div>

    <div>
      <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">E-mail</label>
      <div class="relative">
        <Mail :size="16" class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-text-secondary); opacity: 0.5" />
        <input v-model="email" type="email" required autocomplete="email" class="input-field" style="padding-left: 2.5rem" placeholder="seu@email.com" />
      </div>
    </div>

    <div>
      <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">Senha</label>
      <div class="relative">
        <Lock :size="16" class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-text-secondary); opacity: 0.5" />
        <input v-model="password" type="password" required autocomplete="current-password" class="input-field" style="padding-left: 2.5rem" placeholder="••••••••" />
      </div>
    </div>

    <button type="submit" :disabled="loading" class="btn-primary w-full !py-3 text-base">
      <Loader2 v-if="loading" :size="18" class="animate-spin" />
      <span v-else>Entrar</span>
    </button>

    <p class="text-center text-xs" style="color: var(--color-text-secondary)">
      Não tem uma conta?
      <button type="button" @click="$emit('switch-to-register')" class="font-medium ml-1 hover:underline" style="color: var(--color-primary)">
        Criar conta
      </button>
    </p>
  </form>
</template>
