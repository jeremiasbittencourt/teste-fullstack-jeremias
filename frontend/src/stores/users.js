import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useUsersStore = defineStore('users', () => {
  const users = ref([])
  const meta = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
  const loading = ref(false)

  async function fetch(params = {}) {
    loading.value = true
    try {
      const { data } = await api.get('/users', { params })
      users.value = data.data
      meta.value = data.meta
    } finally {
      loading.value = false
    }
  }

  async function create(payload) {
    const { data } = await api.post('/users', payload)
    return data
  }

  async function update(id, payload) {
    const { data } = await api.put(`/users/${id}`, payload)
    return data
  }

  async function toggleStatus(id) {
    const { data } = await api.patch(`/users/${id}/toggle-status`)
    return data
  }

  async function resetPassword(id, password, password_confirmation) {
    const { data } = await api.patch(`/users/${id}/reset-password`, { password, password_confirmation })
    return data
  }

  return { users, meta, loading, fetch, create, update, toggleStatus, resetPassword }
})
