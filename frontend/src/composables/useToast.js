import { ref } from 'vue'

const toasts = ref([])
let nextId = 0

export function useToast() {
  function addToast(message, type = 'info', duration = 4000) {
    const id = nextId++
    toasts.value.push({ id, message, type, duration })
  }

  function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  function success(message) { addToast(message, 'success') }
  function error(message) { addToast(message, 'danger') }
  function warning(message) { addToast(message, 'warning') }
  function info(message) { addToast(message, 'info') }

  return { toasts, addToast, removeToast, success, error, warning, info }
}
