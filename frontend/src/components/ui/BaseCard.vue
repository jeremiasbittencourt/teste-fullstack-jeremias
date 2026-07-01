<script setup>
defineProps({
  padding: { type: Boolean, default: true },
  hover: { type: Boolean, default: false },
})
</script>

<template>
  <div
    class="card transition-shadow duration-200"
    :class="[padding ? 'p-5' : '', hover ? 'hover:shadow-[var(--shadow-card-hover)] cursor-pointer' : '']"
  >
    <slot />
  </div>
</template>
