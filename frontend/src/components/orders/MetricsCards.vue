<script setup>
import { ShoppingCart, DollarSign, Receipt, XCircle } from 'lucide-vue-next'
import OrderStatusBadge from './OrderStatusBadge.vue'

const props = defineProps({
  metrics: { type: Object, required: true },
})

function money(v) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(v || 0))
}

const cards = [
  { key: 'total_orders', label: 'Total de pedidos', icon: ShoppingCart, color: 'var(--color-primary)', bg: 'var(--color-primary-light)', fmt: (m) => m.total_orders ?? 0 },
  { key: 'total_revenue', label: 'Receita', icon: DollarSign, color: 'var(--color-success)', bg: 'var(--color-success-light)', fmt: (m) => money(m.total_revenue) },
  { key: 'average_ticket', label: 'Ticket médio', icon: Receipt, color: 'var(--color-info)', bg: 'var(--color-info-light)', fmt: (m) => money(m.average_ticket) },
  { key: 'cancellation_rate', label: 'Taxa de cancelamento', icon: XCircle, color: 'var(--color-danger)', bg: 'var(--color-danger-light)', fmt: (m) => `${m.cancellation_rate ?? 0}%` },
]

const statuses = [
  { key: 'pending', label: 'Pendentes' },
  { key: 'approved', label: 'Aprovados' },
  { key: 'cancelled', label: 'Cancelados' },
  { key: 'refunded', label: 'Reembolsados' },
]
</script>

<template>
  <div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
      <div v-for="card in cards" :key="card.key" class="card p-4 animate-fade-in" :style="{ borderLeftWidth: '4px', borderLeftColor: card.color }">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-[24px] font-bold" style="color: var(--color-text-primary)">{{ card.fmt(metrics) }}</p>
            <p class="text-xs font-medium mt-1" style="color: var(--color-text-secondary)">{{ card.label }}</p>
          </div>
          <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :style="{ background: card.bg }">
            <component :is="card.icon" :size="20" :style="{ color: card.color }" />
          </div>
        </div>
      </div>
    </div>

    <!-- Breakdown por status -->
    <div class="card p-4 flex flex-wrap gap-4 items-center">
      <span class="text-xs font-semibold uppercase tracking-wider" style="color: var(--color-text-secondary)">Por status:</span>
      <div v-for="s in statuses" :key="s.key" class="flex items-center gap-2">
        <OrderStatusBadge :status="s.key" :label="s.label" />
        <span class="text-sm font-semibold" style="color: var(--color-text-primary)">{{ metrics.by_status?.[s.key] ?? 0 }}</span>
      </div>
    </div>
  </div>
</template>
