<script setup>
import { ref, onMounted } from 'vue'
import { X, CheckCircle, AlertCircle, AlertTriangle, Info } from 'lucide-vue-next'

const props = defineProps({
  message: { type: String, required: true },
  type: { type: String, default: 'info' },
  duration: { type: Number, default: 4000 },
})

const emit = defineEmits(['close'])
const visible = ref(true)

const config = {
  success: { icon: CheckCircle, bg: 'bg-[var(--color-success-light)]', border: 'border-[var(--color-success)]', text: 'text-[var(--color-success)]', bar: 'bg-[var(--color-success)]' },
  danger: { icon: AlertCircle, bg: 'bg-[var(--color-danger-light)]', border: 'border-[var(--color-danger)]', text: 'text-[var(--color-danger)]', bar: 'bg-[var(--color-danger)]' },
  warning: { icon: AlertTriangle, bg: 'bg-[var(--color-warning-light)]', border: 'border-[var(--color-warning)]', text: 'text-[var(--color-warning)]', bar: 'bg-[var(--color-warning)]' },
  info: { icon: Info, bg: 'bg-[var(--color-info-light)]', border: 'border-[var(--color-info)]', text: 'text-[var(--color-info)]', bar: 'bg-[var(--color-info)]' },
}

const c = config[props.type] || config.info

onMounted(() => {
  if (props.duration > 0) {
    setTimeout(() => { visible.value = false; emit('close') }, props.duration)
  }
})
</script>

<template>
  <div v-if="visible" class="animate-slide-in pointer-events-auto w-80 rounded-lg border shadow-lg overflow-hidden" :class="[c.bg, c.border]">
    <div class="flex items-start gap-3 p-4">
      <component :is="c.icon" :size="18" :class="c.text" class="shrink-0 mt-0.5" />
      <p class="text-sm flex-1" style="color: var(--color-text-primary)">{{ message }}</p>
      <button @click="visible = false; emit('close')" class="shrink-0 p-0.5 rounded hover:bg-black/5">
        <X :size="14" style="color: var(--color-text-secondary)" />
      </button>
    </div>
    <div v-if="duration > 0" class="h-0.5" :class="c.bar" :style="{ animation: `toast-progress ${duration}ms linear forwards` }"></div>
  </div>
</template>
