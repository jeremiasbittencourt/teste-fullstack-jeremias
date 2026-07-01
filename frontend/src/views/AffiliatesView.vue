<script setup>
import { ref, onMounted } from 'vue'
import { Users } from 'lucide-vue-next'
import BaseDrawer from '../components/ui/BaseDrawer.vue'
import BaseSkeleton from '../components/ui/BaseSkeleton.vue'
import { useOrdersStore } from '../stores/orders'
import { useToast } from '../composables/useToast'

const store = useOrdersStore()
const toast = useToast()

const loading = ref(false)
const showDrawer = ref(false)
const summary = ref(null)
const summaryLoading = ref(false)

function money(v) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(v || 0))
}

async function openSummary(affiliate) {
  showDrawer.value = true
  summaryLoading.value = true
  summary.value = null
  try {
    summary.value = await store.affiliateSummary(affiliate.id)
  } catch {
    toast.error('Não foi possível carregar o resumo.')
  } finally {
    summaryLoading.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await store.fetchAffiliates()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold" style="color: var(--color-text-primary)">Afiliados</h1>
      <p class="text-sm mt-0.5" style="color: var(--color-text-secondary)">Clique em um afiliado para ver o resumo</p>
    </div>

    <BaseSkeleton v-if="loading" type="table" :lines="6" />

    <div v-else-if="store.affiliates.length" class="card p-0 overflow-hidden">
      <button
        v-for="a in store.affiliates"
        :key="a.id"
        @click="openSummary(a)"
        class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-[var(--color-primary-light)] focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[var(--color-primary)]"
        style="border-bottom: 1px solid var(--color-border)"
      >
        <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background: var(--color-primary-light)">
          <span class="text-sm font-bold" style="color: var(--color-primary)">{{ a.name?.charAt(0)?.toUpperCase() }}</span>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-medium truncate" style="color: var(--color-text-primary)">{{ a.name }}</p>
          <p class="text-xs truncate" style="color: var(--color-text-secondary)">{{ a.city || '—' }}</p>
        </div>
      </button>
    </div>

    <div v-else class="card p-12 text-center">
      <Users :size="30" class="mx-auto mb-3" style="color: var(--color-text-secondary); opacity: 0.5" />
      <p class="text-sm" style="color: var(--color-text-secondary)">Nenhum afiliado. Rode <code>php artisan orders:sync</code>.</p>
    </div>

    <!-- Drawer de resumo -->
    <BaseDrawer :show="showDrawer" :title="summary?.affiliate?.name || 'Resumo do afiliado'" @close="showDrawer = false">
      <div v-if="summaryLoading"><BaseSkeleton type="card" /></div>
      <div v-else-if="summary" class="grid grid-cols-2 gap-4">
        <div class="card p-4">
          <p class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ summary.total_orders }}</p>
          <p class="text-xs mt-1" style="color: var(--color-text-secondary)">Total de pedidos</p>
        </div>
        <div class="card p-4">
          <p class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ money(summary.revenue) }}</p>
          <p class="text-xs mt-1" style="color: var(--color-text-secondary)">Receita</p>
        </div>
        <div class="card p-4">
          <p class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ money(summary.average_ticket) }}</p>
          <p class="text-xs mt-1" style="color: var(--color-text-secondary)">Ticket médio</p>
        </div>
        <div class="card p-4">
          <p class="text-2xl font-bold" style="color: var(--color-text-primary)">{{ summary.cancellation_rate }}%</p>
          <p class="text-xs mt-1" style="color: var(--color-text-secondary)">Taxa de cancelamento</p>
        </div>
      </div>
    </BaseDrawer>
  </div>
</template>
