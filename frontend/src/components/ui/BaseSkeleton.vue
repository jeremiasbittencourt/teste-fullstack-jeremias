<script setup>
defineProps({
  lines: { type: Number, default: 3 },
  type: { type: String, default: 'text' },
})
</script>

<template>
  <div v-if="type === 'card'" class="card p-5 space-y-3">
    <div class="skeleton h-4 w-1/3"></div>
    <div class="skeleton h-8 w-1/2"></div>
    <div class="skeleton h-3 w-2/3"></div>
  </div>
  <div v-else-if="type === 'table'" class="space-y-2">
    <div class="skeleton h-10 w-full rounded-lg"></div>
    <div v-for="i in lines" :key="i" class="skeleton h-12 w-full rounded-lg"></div>
  </div>
  <div v-else class="space-y-2.5">
    <div v-for="i in lines" :key="i" class="skeleton h-4 rounded" :class="i === lines ? 'w-2/3' : 'w-full'"></div>
  </div>
</template>
