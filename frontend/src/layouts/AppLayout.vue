<script setup>
import { ref } from 'vue'
import TheSidebar from '../components/navigation/TheSidebar.vue'
import TheTopbar from '../components/navigation/TheTopbar.vue'
import ToastContainer from '../components/ui/ToastContainer.vue'
import { useAuth } from '../composables/useAuth'
import { LogOut } from 'lucide-vue-next'

const { handleLogout } = useAuth()
const sidebarCollapsed = ref(false)
const mobileOpen = ref(false)

function toggleMobile() {
  mobileOpen.value = !mobileOpen.value
}
</script>

<template>
  <div class="min-h-screen" style="background: var(--color-bg-page)">
    <TheSidebar
      :collapsed="sidebarCollapsed"
      :mobile-open="mobileOpen"
      @close="mobileOpen = false"
    >
      <template #logout>
        <button
          @click="handleLogout"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 text-[var(--color-text-sidebar)] hover:text-white hover:bg-[var(--color-bg-sidebar-hover)]"
        >
          <LogOut :size="20" />
          <span v-if="!sidebarCollapsed">Sair</span>
        </button>
      </template>
    </TheSidebar>

    <!-- Main area -->
    <div
      class="transition-all duration-200"
      :class="sidebarCollapsed ? 'lg:ml-[70px]' : 'lg:ml-64'"
    >
      <TheTopbar @toggle-sidebar="toggleMobile">
        <template #logout>
          <button @click="handleLogout" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-gray-50 transition-colors" style="color: var(--color-danger)">
            <LogOut :size="16" />
            Sair
          </button>
        </template>
      </TheTopbar>

      <main class="p-4 lg:p-6">
        <div class="max-w-[1440px] mx-auto">
          <router-view v-slot="{ Component }">
            <Transition name="page" mode="out-in">
              <component :is="Component" />
            </Transition>
          </router-view>
        </div>
      </main>
    </div>

    <ToastContainer />
  </div>
</template>

<style scoped>
.page-enter-active { transition: opacity 150ms ease, transform 150ms ease; }
.page-leave-active { transition: opacity 100ms ease; }
.page-enter-from { opacity: 0; transform: translateY(4px); }
.page-leave-to { opacity: 0; }
</style>
