<script setup>
import { X } from 'lucide-vue-next'
import { onMounted, onUnmounted } from 'vue'

defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: '' },
  maxWidth: { type: String, default: 'max-w-lg' },
})

const emit = defineEmits(['close'])

function onKeydown(e) {
  if (e.key === 'Escape') emit('close')
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => document.removeEventListener('keydown', onKeydown))
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" @click="emit('close')"></div>
        <Transition
          enter-active-class="duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="show" class="relative bg-white rounded-xl shadow-xl w-full flex flex-col max-h-[90vh]" :class="maxWidth" style="box-shadow: var(--shadow-dropdown)">
            <!-- Header (fixo) -->
            <div v-if="title || $slots.header" class="flex items-center justify-between px-6 py-4 border-b shrink-0" style="border-color: var(--color-border)">
              <slot name="header">
                <h3 class="text-base font-semibold" style="color: var(--color-text-primary)">{{ title }}</h3>
              </slot>
              <button @click="emit('close')" class="p-1 rounded-md hover:bg-gray-100 transition-colors" style="color: var(--color-text-secondary)">
                <X :size="18" />
              </button>
            </div>
            <!-- Body (scroll interno) -->
            <div class="px-6 py-5 overflow-y-auto flex-1 min-h-0">
              <slot />
            </div>
            <!-- Footer (fixo) -->
            <div v-if="$slots.footer" class="flex items-center justify-end gap-3 px-6 py-4 border-t shrink-0 flex-wrap" style="border-color: var(--color-border)">
              <slot name="footer" />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
