import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../services/api'

export const useOrdersStore = defineStore('orders', () => {
  const orders = ref([])
  const meta = ref({})
  const loading = ref(false)

  const metrics = ref(null)
  const metricsLoading = ref(false)

  const affiliates = ref([])

  /**
   * Busca a lista paginada de pedidos. `params` já vem montado a partir da URL
   * (fonte de verdade dos filtros).
   */
  async function fetchOrders(params) {
    loading.value = true
    try {
      const { data } = await api.get('/orders', { params })
      orders.value = data.data
      meta.value = data.meta
    } finally {
      loading.value = false
    }
  }

  async function fetchMetrics() {
    metricsLoading.value = true
    try {
      const { data } = await api.get('/orders/metrics')
      metrics.value = data.data
    } finally {
      metricsLoading.value = false
    }
  }

  async function fetchAffiliates() {
    if (affiliates.value.length) return
    const { data } = await api.get('/affiliates', { params: { per_page: 200 } })
    affiliates.value = data.data
  }

  async function fetchOrder(id) {
    const { data } = await api.get(`/orders/${id}`)
    return data.data
  }

  async function updateStatus(id, status) {
    const { data } = await api.post(`/orders/${id}/status`, { status })
    return data.data
  }

  async function affiliateSummary(id) {
    const { data } = await api.get(`/affiliates/${id}/summary`)
    return data.data
  }

  return {
    orders, meta, loading,
    metrics, metricsLoading,
    affiliates,
    fetchOrders, fetchMetrics, fetchAffiliates, fetchOrder, updateStatus, affiliateSummary,
  }
})
