import { useAuthStore } from '../stores/auth'
import { useRouter } from 'vue-router'
import { ref } from 'vue'

export function useAuth() {
  const authStore = useAuthStore()
  const router = useRouter()
  const loading = ref(false)
  const error = ref(null)

  async function handleLogin(email, password) {
    loading.value = true
    error.value = null
    try {
      await authStore.login(email, password)
      router.push({ name: 'dashboard' })
    } catch (e) {
      error.value = e.response?.data?.message || e.response?.data?.errors?.email?.[0] || 'Erro ao realizar login.'
    } finally {
      loading.value = false
    }
  }

  async function handleLogout() {
    await authStore.logout()
    router.push({ name: 'login' })
  }

  return { handleLogin, handleLogout, loading, error }
}
