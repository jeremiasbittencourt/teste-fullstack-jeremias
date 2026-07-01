<script setup>
import { ref } from 'vue'
import LoginForm from '../components/auth/LoginForm.vue'
import RegisterForm from '../components/auth/RegisterForm.vue'

const mode = ref('login')
const successMessage = ref('')

function switchToRegister() {
  successMessage.value = ''
  mode.value = 'register'
}

function switchToLogin() {
  mode.value = 'login'
}

function onRegisterSuccess(message) {
  successMessage.value = message
  mode.value = 'login'
}
</script>

<template>
  <div class="w-full max-w-[420px] animate-fade-in">
    <!-- Logo -->
    <div class="text-center mb-8">
      <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg" style="background: var(--color-primary)">
        <span class="text-white font-bold text-2xl">ST</span>
      </div>
      <h1 class="text-2xl font-bold text-white">Smart Team</h1>
      <p class="text-sm mt-1" style="color: var(--color-text-sidebar)">Controle de Chamados de Onboarding</p>
    </div>

    <!-- Card -->
    <div class="card p-8">
      <h2 v-if="mode === 'register'" class="text-lg font-semibold mb-5" style="color: var(--color-text-primary)">Criar sua conta</h2>

      <Transition
        mode="out-in"
        enter-active-class="transition-all duration-200 ease-out"
        enter-from-class="opacity-0 translate-x-2"
        enter-to-class="opacity-100 translate-x-0"
        leave-active-class="transition-all duration-150 ease-in"
        leave-from-class="opacity-100 translate-x-0"
        leave-to-class="opacity-0 -translate-x-2"
      >
        <LoginForm
          v-if="mode === 'login'"
          :success-message="successMessage"
          @switch-to-register="switchToRegister"
        />
        <RegisterForm
          v-else
          @switch-to-login="switchToLogin"
          @success="onRegisterSuccess"
        />
      </Transition>
    </div>

    <p v-if="mode === 'login'" class="text-center text-xs mt-6" style="color: var(--color-text-sidebar); opacity: 0.6">
      admin@sistema.com / Admin@2024
    </p>
  </div>
</template>
