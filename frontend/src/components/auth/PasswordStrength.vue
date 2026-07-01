<script setup>
import { computed } from 'vue'

const props = defineProps({
  password: { type: String, default: '' },
})

const score = computed(() => {
  const p = props.password || ''
  if (!p) return 0
  let s = 0
  if (p.length >= 8) s++
  if (/[A-Z]/.test(p)) s++
  if (/\d/.test(p)) s++
  if (p.length >= 12 || /[^A-Za-z0-9]/.test(p)) s++
  return s
})

const level = computed(() => {
  if (!props.password) return null
  if (score.value <= 1) return { label: 'Fraca', color: 'var(--color-danger)', width: '33%' }
  if (score.value <= 2) return { label: 'Média', color: 'var(--color-warning)', width: '66%' }
  return { label: 'Forte', color: 'var(--color-success)', width: '100%' }
})
</script>

<template>
  <div v-if="level" class="mt-2">
    <div class="flex items-center justify-between text-[11px] mb-1">
      <span style="color: var(--color-text-secondary)">Força da senha</span>
      <span class="font-medium" :style="{ color: level.color }">{{ level.label }}</span>
    </div>
    <div class="h-1 rounded-full overflow-hidden" style="background: var(--color-border)">
      <div class="h-full transition-all duration-300" :style="{ width: level.width, background: level.color }"></div>
    </div>
  </div>
</template>
