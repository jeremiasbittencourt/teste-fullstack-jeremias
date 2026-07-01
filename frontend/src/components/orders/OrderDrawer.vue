<script setup>
import { ref, watch } from 'vue'
import BaseDrawer from '../ui/BaseDrawer.vue'
import BaseSkeleton from '../ui/BaseSkeleton.vue'
import OrderStatusBadge from './OrderStatusBadge.vue'
import OrderTimeline from './OrderTimeline.vue'
import { useOrdersStore } from '../../stores/orders'
import { useToast } from '../../composables/useToast'

const props = defineProps({
  show: { type: Boolean, default: false },
  orderId: { type: [Number, String], default: null },
})

const emit = defineEmits(['close', 'updated'])

const store = useOrdersStore()
const toast = useToast()

const order = ref(null)
const loading = ref(false)
const selectedStatus = ref('')
const saving = ref(false)
const transitionError = ref('')

async function load() {
  if (!props.orderId) return
  loading.value = true
  transitionError.value = ''
  selectedStatus.value = ''
  try {
    order.value = await store.fetchOrder(props.orderId)
  } catch {
    toast.error('Não foi possível carregar o pedido.')
  } finally {
    loading.value = false
  }
}

watch(() => [props.show, props.orderId], ([show]) => {
  if (show && props.orderId) load()
})

function money(v) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(v || 0))
}

async function applyStatus() {
  if (!selectedStatus.value) return
  saving.value = true
  transitionError.value = ''
  try {
    await store.updateStatus(order.value.id, selectedStatus.value)
    toast.success('Status atualizado.')
    emit('updated')
    await load()
  } catch (e) {
    // Feedback do 422 vindo do backend (transição rejeitada pela máquina de estados).
    const errors = e.response?.data?.errors
    transitionError.value = errors?.status?.[0] || 'Não foi possível atualizar o status.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseDrawer :show="show" :title="order ? `Pedido #${order.id}` : 'Pedido'" @close="emit('close')">
    <div v-if="loading">
      <BaseSkeleton type="card" />
      <div class="mt-4"><BaseSkeleton :lines="4" /></div>
    </div>

    <div v-else-if="order" class="space-y-6">
      <!-- Resumo -->
      <div class="flex items-center justify-between">
        <OrderStatusBadge :status="order.status" :label="order.status_label" />
        <span class="text-lg font-bold" style="color: var(--color-text-primary)">{{ money(order.total) }}</span>
      </div>

      <dl class="grid grid-cols-2 gap-3 text-sm">
        <div>
          <dt class="text-[11px] uppercase tracking-wider" style="color: var(--color-text-secondary)">Afiliado</dt>
          <dd style="color: var(--color-text-primary)">{{ order.affiliate?.name || '—' }}</dd>
        </div>
        <div>
          <dt class="text-[11px] uppercase tracking-wider" style="color: var(--color-text-secondary)">Cidade</dt>
          <dd style="color: var(--color-text-primary)">{{ order.affiliate?.city || '—' }}</dd>
        </div>
        <div>
          <dt class="text-[11px] uppercase tracking-wider" style="color: var(--color-text-secondary)">Data</dt>
          <dd style="color: var(--color-text-primary)">{{ new Date(order.created_at).toLocaleDateString('pt-BR') }}</dd>
        </div>
        <div>
          <dt class="text-[11px] uppercase tracking-wider" style="color: var(--color-text-secondary)">Cód. externo</dt>
          <dd style="color: var(--color-text-primary)">#{{ order.external_id }}</dd>
        </div>
      </dl>

      <!-- Itens -->
      <div>
        <h3 class="text-sm font-semibold mb-2" style="color: var(--color-text-primary)">Itens</h3>
        <div class="rounded-lg overflow-hidden" style="border: 1px solid var(--color-border)">
          <div
            v-for="item in order.items"
            :key="item.id"
            class="flex items-center justify-between px-3 py-2 text-sm"
            style="border-bottom: 1px solid var(--color-border)"
          >
            <span class="truncate mr-2" style="color: var(--color-text-primary)">{{ item.title }}</span>
            <span class="shrink-0" style="color: var(--color-text-secondary)">{{ item.quantity }}× {{ money(item.unit_price) }}</span>
          </div>
          <p v-if="!order.items?.length" class="px-3 py-3 text-xs text-center" style="color: var(--color-text-secondary)">Sem itens.</p>
        </div>
      </div>

      <!-- Mudança de status -->
      <div>
        <h3 class="text-sm font-semibold mb-2" style="color: var(--color-text-primary)">Alterar status</h3>
        <div v-if="order.allowed_transitions?.length" class="flex gap-2">
          <select
            v-model="selectedStatus"
            class="flex-1 text-sm rounded-lg px-3 py-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-primary)]"
            style="border: 1px solid var(--color-border); color: var(--color-text-primary)"
            aria-label="Selecionar novo status"
          >
            <option value="">Selecione…</option>
            <option v-for="t in order.allowed_transitions" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
          <button
            class="btn-primary btn-sm"
            :disabled="!selectedStatus || saving"
            @click="applyStatus"
          >
            {{ saving ? 'Aplicando…' : 'Aplicar' }}
          </button>
        </div>
        <p v-else class="text-xs" style="color: var(--color-text-secondary)">
          Este pedido está em um status final — não há transições disponíveis.
        </p>
        <p v-if="transitionError" class="text-xs mt-2" style="color: var(--color-danger)" role="alert">
          {{ transitionError }}
        </p>
      </div>

      <!-- Histórico -->
      <div>
        <h3 class="text-sm font-semibold mb-3" style="color: var(--color-text-primary)">Histórico de status</h3>
        <OrderTimeline :logs="order.status_logs || []" />
      </div>
    </div>
  </BaseDrawer>
</template>
