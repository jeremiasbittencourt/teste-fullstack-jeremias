<script setup>
import { watch, onBeforeUnmount } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: '' },
})

const emit = defineEmits(['close'])

function onKeydown(e) {
  if (e.key === 'Escape') emit('close')
}

watch(() => props.show, (open) => {
  if (open) {
    document.addEventListener('keydown', onKeydown)
    document.body.style.overflow = 'hidden'
  } else {
    document.removeEventListener('keydown', onKeydown)
    document.body.style.overflow = ''
  }
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <!-- Overlay -->
    <Transition enter-active-class="duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="show" class="fixed inset-0 bg-black/40 z-50" @click="emit('close')" />
    </Transition>

    <!-- Painel -->
    <Transition enter-active-class="transition-transform duration-250 ease-out" enter-from-class="translate-x-full" enter-to-class="translate-x-0" leave-active-class="transition-transform duration-200 ease-in" leave-from-class="translate-x-0" leave-to-class="translate-x-full">
      <aside
        v-if="show"
        class="fixed top-0 right-0 h-full z-50 w-full sm:max-w-md flex flex-col shadow-2xl"
        style="background: var(--color-bg-card, #fff)"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
      >
        <header class="flex items-center justify-between h-16 px-5 shrink-0" style="border-bottom: 1px solid var(--color-border)">
          <h2 class="text-base font-semibold" style="color: var(--color-text-primary)">{{ title }}</h2>
          <button
            @click="emit('close')"
            class="p-1.5 rounded-md transition-colors hover:bg-gray-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)]"
            style="color: var(--color-text-secondary)"
            aria-label="Fechar painel"
          >
            <X :size="20" />
          </button>
        </header>

        <div class="flex-1 overflow-y-auto p-5">
          <slot />
        </div>

        <footer v-if="$slots.footer" class="shrink-0 p-5" style="border-top: 1px solid var(--color-border)">
          <slot name="footer" />
        </footer>
      </aside>
    </Transition>
  </Teleport>
</template>
