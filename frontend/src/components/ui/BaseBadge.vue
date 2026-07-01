<script setup>
defineProps({
  variant: { type: String, default: 'default' },
  pulse: { type: Boolean, default: false },
})

const variants = {
  default: 'bg-gray-100 text-gray-700',
  success: 'bg-[var(--color-success-light)] text-[var(--color-success)]',
  warning: 'bg-[var(--color-warning-light)] text-[var(--color-warning)]',
  danger: 'bg-[var(--color-danger-light)] text-[var(--color-danger)]',
  info: 'bg-[var(--color-info-light)] text-[var(--color-info)]',
  primary: 'bg-[var(--color-primary-light)] text-[var(--color-primary)]',
}
</script>

<template>
  <span
    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
    :class="[variants[variant] || variants.default, pulse ? 'pulse-badge' : '']"
  >
    <slot />
  </span>
</template>
