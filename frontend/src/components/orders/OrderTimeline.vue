<script setup>
defineProps({
  logs: { type: Array, default: () => [] },
})

function fmt(dt) {
  return dt ? new Date(dt).toLocaleString('pt-BR') : ''
}
</script>

<template>
  <div v-if="logs.length" class="ml-2 space-y-0" style="border-left: 2px solid var(--color-border)">
    <div v-for="log in logs" :key="log.id" class="relative pl-6 pb-4">
      <div class="absolute left-[-5px] top-1 w-2 h-2 rounded-full" style="background: var(--color-primary)"></div>
      <p class="text-xs" style="color: var(--color-text-secondary)">{{ fmt(log.created_at) }}</p>
      <p class="text-sm mt-0.5" style="color: var(--color-text-primary)">
        <template v-if="log.previous_status">
          <strong>{{ log.previous_status }}</strong> → <strong>{{ log.new_status }}</strong>
        </template>
        <template v-else>
          Pedido criado como <strong>{{ log.new_status }}</strong>
        </template>
        <span v-if="log.changed_by" class="ml-1" style="color: var(--color-text-secondary)">por {{ log.changed_by }}</span>
      </p>
    </div>
  </div>
  <p v-else class="text-xs py-3 text-center" style="color: var(--color-text-secondary)">
    Nenhuma alteração de status registrada.
  </p>
</template>
