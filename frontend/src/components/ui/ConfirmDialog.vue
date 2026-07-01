<script setup>
import BaseModal from './BaseModal.vue'
import { AlertTriangle } from 'lucide-vue-next'

defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Confirmar ação' },
  message: { type: String, default: 'Tem certeza que deseja realizar esta ação?' },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  danger: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <BaseModal :show="show" :title="title" max-width="max-w-md" @close="emit('cancel')">
    <div class="flex gap-4">
      <div class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center" :class="danger ? 'bg-[var(--color-danger-light)]' : 'bg-[var(--color-warning-light)]'">
        <AlertTriangle :size="20" :class="danger ? 'text-[var(--color-danger)]' : 'text-[var(--color-warning)]'" />
      </div>
      <p class="text-sm" style="color: var(--color-text-secondary)">{{ message }}</p>
    </div>
    <template #footer>
      <button @click="emit('cancel')" class="btn-secondary btn-sm">{{ cancelText }}</button>
      <button @click="emit('confirm')" :disabled="loading" :class="danger ? 'btn-danger' : 'btn-primary'" class="btn-sm">
        {{ loading ? 'Aguarde...' : confirmText }}
      </button>
    </template>
  </BaseModal>
</template>
