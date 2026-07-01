<script setup>
import { Ticket, CheckCircle, Clock, AlertTriangle, Users } from 'lucide-vue-next'

defineProps({
  summary: { type: Object, required: true },
  activeFilter: { type: String, default: '' },
})

const emit = defineEmits(['filter'])

const cards = [
  { key: 'total_open', label: 'Abertos', filter: 'open', icon: Ticket, border: 'var(--color-info)', iconBg: 'var(--color-info-light)', iconColor: 'var(--color-info)' },
  { key: 'total_resolved', label: 'Finalizados', filter: 'resolved', icon: CheckCircle, border: 'var(--color-success)', iconBg: 'var(--color-success-light)', iconColor: 'var(--color-success)' },
  { key: 'total_pending', label: 'Pendentes', filter: 'pending', icon: Clock, border: 'var(--color-warning)', iconBg: 'var(--color-warning-light)', iconColor: 'var(--color-warning)' },
  { key: 'total_blocked', label: 'Com Problema', filter: 'blocked', icon: AlertTriangle, border: 'var(--color-danger)', iconBg: 'var(--color-danger-light)', iconColor: 'var(--color-danger)' },
  { key: 'total_employees', label: 'Colaboradores', filter: '', icon: Users, border: 'var(--color-primary)', iconBg: 'var(--color-primary-light)', iconColor: 'var(--color-primary)' },
]

function onClick(card) {
  if (!card.filter) return
  emit('filter', card.filter)
}
</script>

<template>
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
    <div
      v-for="card in cards"
      :key="card.key"
      class="card p-4 transition-all duration-200 animate-fade-in"
      :class="[
        card.filter ? 'cursor-pointer hover:shadow-[var(--shadow-card-hover)]' : '',
        activeFilter === card.filter ? 'ring-2 ring-offset-1' : ''
      ]"
      :style="{
        borderLeftWidth: '4px',
        borderLeftColor: card.border,
        '--tw-ring-color': activeFilter === card.filter ? card.border : 'transparent',
      }"
      @click="onClick(card)"
    >
      <div class="flex items-start justify-between">
        <div>
          <p class="text-[28px] font-bold animate-count" style="color: var(--color-text-primary)">{{ summary[card.key] ?? 0 }}</p>
          <p class="text-xs font-medium mt-1" style="color: var(--color-text-secondary)">{{ card.label }}</p>
        </div>
        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0" :style="{ background: card.iconBg }">
          <component :is="card.icon" :size="20" :style="{ color: card.iconColor }" />
        </div>
      </div>
    </div>
  </div>
</template>
