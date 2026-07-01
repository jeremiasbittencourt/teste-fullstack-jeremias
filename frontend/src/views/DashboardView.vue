<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { RefreshCw } from 'lucide-vue-next'
import MetricsCards from '../components/orders/MetricsCards.vue'
import BaseSkeleton from '../components/ui/BaseSkeleton.vue'
import { useOrdersStore } from '../stores/orders'

const store = useOrdersStore()
const now = ref(Date.now())
let refreshTimer = null
let clockTimer = null

const metrics = computed(() => store.metrics)

// Indicador de cache: "atualizado há X" a partir do generated_at do payload.
const cacheAge = computed(() => {
  if (!metrics.value?.generated_at) return ''
  const diffMs = now.value - new Date(metrics.value.generated_at).getTime()
  const min = Math.floor(diffMs / 60000)
  if (min <= 0) return 'agora mesmo'
  if (min === 1) return 'há 1 min'
  if (min < 60) return `há ${min} min`
  const h = Math.floor(min / 60)
  return `há ${h}h`
})

async function refresh() {
  await store.fetchMetrics()
  now.value = Date.now()
}

onMounted(() => {
  refresh()
  // Refresh automático a cada 60s, sem recarregar a página.
  refreshTimer = setInterval(refresh, 60_000)
  // Atualiza o "há X min" a cada 30s.
  clockTimer = setInterval(() => { now.value = Date.now() }, 30_000)
})

onBeforeUnmount(() => {
  clearInterval(refreshTimer)
  clearInterval(clockTimer)
})
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
      <div>
        <h1 class="text-2xl font-bold" style="color: var(--color-text-primary)">Dashboard</h1>
        <p class="text-sm mt-0.5" style="color: var(--color-text-secondary)">Métricas de pedidos</p>
      </div>
      <div class="flex items-center gap-3">
        <span v-if="cacheAge" class="text-xs" style="color: var(--color-text-secondary)">
          Atualizado {{ cacheAge }}
        </span>
        <button
          class="btn-secondary btn-sm"
          :disabled="store.metricsLoading"
          @click="refresh"
          aria-label="Atualizar métricas"
        >
          <RefreshCw :size="14" :class="{ 'animate-spin': store.metricsLoading }" />
          Atualizar
        </button>
      </div>
    </div>

    <div v-if="!metrics && store.metricsLoading" class="space-y-4">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <BaseSkeleton v-for="i in 4" :key="i" type="card" />
      </div>
    </div>

    <MetricsCards v-else-if="metrics" :metrics="metrics" />
  </div>
</template>
