<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { Menu, Bell, ChevronDown, LogOut, User } from 'lucide-vue-next'
import api from '../../services/api'

const emit = defineEmits(['toggle-sidebar'])
const route = useRoute()
const authStore = useAuthStore()
const staleCount = ref(0)
const showUserMenu = ref(false)

const pageTitles = {
  'dashboard': 'Dashboard',
  'ticket-create': 'Chamados > Novo Chamado',
  'ticket-report': 'Chamados > Relatório',
  'employee-create': 'Colaboradores > Novo Colaborador',
  'employee-detail': 'Colaboradores > Detalhe',
  'admin-catalog': 'Cadastros',
}

function closeUserMenu(e) {
  if (!e.target.closest('.user-menu-container')) {
    showUserMenu.value = false
  }
}

onMounted(async () => {
  document.addEventListener('click', closeUserMenu)
  try {
    const { data } = await api.get('/tickets', { params: { status: 'blocked,form_modified', per_page: 1 } })
    staleCount.value = data.meta?.total || 0
  } catch {}
})
</script>

<template>
  <header class="sticky top-0 z-30 h-16 flex items-center justify-between px-4 lg:px-6 border-b" style="background: var(--color-bg-card); border-color: var(--color-border)">
    <div class="flex items-center gap-4">
      <button @click="emit('toggle-sidebar')" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors" style="color: var(--color-text-secondary)">
        <Menu :size="20" />
      </button>
      <h1 class="text-base font-semibold" style="color: var(--color-text-primary)">
        {{ pageTitles[route.name] || 'Smart Team' }}
      </h1>
    </div>

    <div class="flex items-center gap-3">
      <!-- Notifications bell -->
      <button class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors" style="color: var(--color-text-secondary)">
        <Bell :size="20" />
        <span v-if="staleCount > 0" class="absolute -top-0.5 -right-0.5 w-5 h-5 flex items-center justify-center text-[10px] font-bold text-white rounded-full pulse-badge" style="background: var(--color-danger)">
          {{ staleCount > 99 ? '99+' : staleCount }}
        </span>
      </button>

      <!-- User dropdown -->
      <div class="relative user-menu-container">
        <button @click="showUserMenu = !showUserMenu" class="flex items-center gap-2 p-1.5 pr-3 rounded-lg hover:bg-gray-100 transition-colors">
          <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: var(--color-primary-light)">
            <span class="text-xs font-semibold" style="color: var(--color-primary)">{{ authStore.userName?.charAt(0)?.toUpperCase() }}</span>
          </div>
          <span class="hidden sm:block text-sm font-medium" style="color: var(--color-text-primary)">{{ authStore.userName }}</span>
          <ChevronDown :size="14" style="color: var(--color-text-secondary)" />
        </button>

        <Transition enter-active-class="duration-150 ease-out" enter-from-class="opacity-0 scale-95 -translate-y-1" enter-to-class="opacity-100 scale-100" leave-active-class="duration-100 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95 -translate-y-1">
          <div v-if="showUserMenu" class="absolute right-0 mt-2 w-48 rounded-lg border py-1" style="background: var(--color-bg-card); border-color: var(--color-border); box-shadow: var(--shadow-dropdown)">
            <div class="px-3 py-2 border-b" style="border-color: var(--color-border)">
              <p class="text-sm font-medium" style="color: var(--color-text-primary)">{{ authStore.userName }}</p>
              <p class="text-xs" style="color: var(--color-text-secondary)">{{ authStore.user?.email }}</p>
            </div>
            <button class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 transition-colors" style="color: var(--color-text-secondary)">
              <User :size="16" />
              Meu Perfil
            </button>
            <slot name="logout" />
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>
