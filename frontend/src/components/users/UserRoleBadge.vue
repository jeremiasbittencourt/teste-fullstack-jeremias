<script setup>
import { computed } from 'vue'
import { Shield, User } from 'lucide-vue-next'

const props = defineProps({
  role: { type: String, required: true },
})

const config = computed(() => props.role === 'admin'
  ? { label: 'Admin', icon: Shield, bg: 'var(--color-primary-light)', color: 'var(--color-primary)' }
  : { label: 'Operador', icon: User, bg: 'var(--color-bg-page)', color: 'var(--color-text-secondary)' }
)
</script>

<template>
  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium" :style="{ background: config.bg, color: config.color }">
    <component :is="config.icon" :size="12" />
    {{ config.label }}
  </span>
</template>
