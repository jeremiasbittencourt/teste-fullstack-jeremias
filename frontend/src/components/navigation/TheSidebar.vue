<script setup>
import { ref, computed, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import {
  LayoutDashboard, ShoppingCart, Users, UserCog, ChevronDown, X
} from 'lucide-vue-next'

const props = defineProps({
  collapsed: { type: Boolean, default: false },
  mobileOpen: { type: Boolean, default: false },
})
const emit = defineEmits(['close'])

const route = useRoute()
const authStore = useAuthStore()
const expandedMenus = ref(new Set())

const menuItems = computed(() => [
  {
    key: 'dashboard',
    label: 'Dashboard',
    icon: LayoutDashboard,
    to: { name: 'dashboard' },
  },
  {
    key: 'orders',
    label: 'Pedidos',
    icon: ShoppingCart,
    to: { name: 'orders' },
  },
  {
    key: 'affiliates',
    label: 'Afiliados',
    icon: Users,
    to: { name: 'affiliates' },
  },
  {
    key: 'usuarios',
    label: 'Usuários',
    icon: UserCog,
    show: authStore.isAdmin,
    to: { name: 'users' },
  },
])

function toggleMenu(key) {
  if (expandedMenus.value.has(key)) {
    expandedMenus.value.delete(key)
  } else {
    expandedMenus.value.add(key)
  }
}

function isActive(item) {
  if (item.to) return route.name === item.to.name
  if (item.children) return item.children.some(c => route.name === c.to.name)
  return false
}

// Auto-expand parent menu when navigating to a child
watch(() => route.name, () => {
  menuItems.value.forEach(item => {
    if (item.children?.some(c => route.name === c.to.name)) {
      expandedMenus.value.add(item.key)
    }
  })
}, { immediate: true })
</script>

<template>
  <!-- Mobile overlay -->
  <Transition enter-active-class="duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
    <div v-if="mobileOpen" class="fixed inset-0 bg-black/50 z-40 lg:hidden" @click="emit('close')"></div>
  </Transition>

  <!-- Sidebar -->
  <aside
    class="fixed top-0 left-0 h-full z-50 flex flex-col transition-all duration-200 ease-in-out"
    :class="[
      mobileOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0',
      collapsed ? 'lg:w-[70px]' : 'lg:w-64',
    ]"
    style="background: var(--color-bg-sidebar)"
  >
    <!-- Logo -->
    <div class="flex items-center justify-between h-16 px-4 shrink-0" style="border-bottom: 1px solid rgba(255,255,255,0.08)">
      <RouterLink :to="{ name: 'dashboard' }" class="flex items-center gap-3 overflow-hidden" @click="emit('close')">
        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0" style="background: var(--color-primary)">
          <span class="text-white font-bold text-sm">OM</span>
        </div>
        <span v-if="!collapsed" class="text-white font-semibold text-lg whitespace-nowrap">Orders</span>
      </RouterLink>
      <button v-if="mobileOpen" @click="emit('close')" class="lg:hidden p-1 rounded" style="color: var(--color-text-sidebar)">
        <X :size="20" />
      </button>
    </div>

    <!-- User info -->
    <div v-if="!collapsed" class="px-4 py-4 shrink-0" style="border-bottom: 1px solid rgba(255,255,255,0.08)">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background: var(--color-primary); opacity: 0.8">
          <span class="text-white font-medium text-sm">{{ authStore.userName?.charAt(0)?.toUpperCase() }}</span>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-medium truncate" style="color: var(--color-text-sidebar-active)">{{ authStore.userName }}</p>
          <p class="text-xs truncate" style="color: var(--color-text-sidebar)">{{ authStore.user?.role_label }}</p>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-1">
      <template v-for="item in menuItems" :key="item.key">
        <template v-if="item.show !== false">
          <!-- Simple link -->
          <RouterLink
            v-if="item.to && !item.children"
            :to="item.to"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 group"
            :class="isActive(item)
              ? 'text-white bg-[var(--color-bg-sidebar-active)]'
              : 'text-[var(--color-text-sidebar)] hover:text-white hover:bg-[var(--color-bg-sidebar-hover)]'"
            @click="emit('close')"
          >
            <component :is="item.icon" :size="20" class="shrink-0" />
            <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
          </RouterLink>

          <!-- Submenu parent -->
          <div v-else-if="item.children">
            <button
              @click="toggleMenu(item.key)"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150"
              :class="isActive(item)
                ? 'text-white bg-[var(--color-bg-sidebar-active)]'
                : 'text-[var(--color-text-sidebar)] hover:text-white hover:bg-[var(--color-bg-sidebar-hover)]'"
            >
              <component :is="item.icon" :size="20" class="shrink-0" />
              <span v-if="!collapsed" class="truncate flex-1 text-left">{{ item.label }}</span>
              <ChevronDown v-if="!collapsed" :size="16" class="shrink-0 transition-transform duration-200" :class="{ 'rotate-180': expandedMenus.has(item.key) }" />
            </button>

            <!-- Children -->
            <Transition
              enter-active-class="transition-all duration-200 ease-out overflow-hidden"
              enter-from-class="max-h-0 opacity-0"
              enter-to-class="max-h-96 opacity-100"
              leave-active-class="transition-all duration-150 ease-in overflow-hidden"
              leave-from-class="max-h-96 opacity-100"
              leave-to-class="max-h-0 opacity-0"
            >
              <div v-if="expandedMenus.has(item.key) && !collapsed" class="mt-1 ml-4 pl-4 space-y-0.5" style="border-left: 1px solid rgba(255,255,255,0.08)">
                <RouterLink
                  v-for="child in item.children"
                  :key="child.label"
                  :to="child.to"
                  class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors duration-150"
                  :class="route.name === child.to.name
                    ? 'text-white bg-[var(--color-bg-sidebar-hover)]'
                    : 'text-[var(--color-text-sidebar)] hover:text-white hover:bg-[var(--color-bg-sidebar-hover)]'"
                  @click="emit('close')"
                >
                  <component :is="child.icon" :size="16" class="shrink-0" />
                  <span class="truncate">{{ child.label }}</span>
                </RouterLink>
              </div>
            </Transition>
          </div>
        </template>
      </template>
    </nav>

    <!-- Logout -->
    <div class="shrink-0 px-3 py-3" style="border-top: 1px solid rgba(255,255,255,0.08)">
      <slot name="logout" />
    </div>
  </aside>
</template>
