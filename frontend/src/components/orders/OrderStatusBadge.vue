<script setup>
import BaseBadge from '../ui/BaseBadge.vue'

defineProps({
  status: { type: String, required: true },
  label: { type: String, default: '' },
})

// Mapeia a cor semântica vinda do backend (OrderStatus::color) para a variante do badge.
const colorToVariant = {
  yellow: 'warning',
  green: 'success',
  red: 'danger',
  purple: 'primary',
}

const statusColor = {
  pending: 'yellow',
  approved: 'green',
  cancelled: 'red',
  refunded: 'purple',
}
</script>

<template>
  <BaseBadge :variant="colorToVariant[statusColor[status]] || 'default'">
    {{ label || status }}
  </BaseBadge>
</template>
