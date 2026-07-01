<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Search, X, Ban } from 'lucide-vue-next'
import OrdersTable from '../components/orders/OrdersTable.vue'
import OrderDrawer from '../components/orders/OrderDrawer.vue'
import { useOrdersStore } from '../stores/orders'
import { useToast } from '../composables/useToast'

const route = useRoute()
const router = useRouter()
const store = useOrdersStore()
const toast = useToast()

const PER_PAGE = 20
const FILTER_KEYS = ['affiliate_id', 'status', 'date_from', 'date_to', 'min_value', 'max_value', 'sort_by', 'sort_dir', 'page']

// Form local espelha os filtros; a URL é a fonte de verdade.
const form = reactive({ affiliate_id: '', status: '', date_from: '', date_to: '', min_value: '', max_value: '' })
const sortBy = ref('created_at')
const sortDir = ref('desc')
const page = ref(1)
const selected = ref([])

const statusOptions = [
  { value: 'pending', label: 'Pendente' },
  { value: 'approved', label: 'Aprovado' },
  { value: 'cancelled', label: 'Cancelado' },
  { value: 'refunded', label: 'Reembolsado' },
]

// ── Sincronização URL → estado + fetch ──
function syncFromRoute() {
  const q = route.query
  form.affiliate_id = q.affiliate_id || ''
  form.status = q.status || ''
  form.date_from = q.date_from || ''
  form.date_to = q.date_to || ''
  form.min_value = q.min_value || ''
  form.max_value = q.max_value || ''
  sortBy.value = q.sort_by || 'created_at'
  sortDir.value = q.sort_dir || 'desc'
  page.value = Number(q.page) || 1
}

const listParams = computed(() => {
  const p = { per_page: PER_PAGE }
  FILTER_KEYS.forEach((k) => {
    const v = route.query[k]
    if (v !== undefined && v !== '' && v !== null) p[k] = v
  })
  return p
})

// Refaz o fetch só quando um filtro relevante muda (ignora ?order= do drawer).
watch(
  () => FILTER_KEYS.map((k) => route.query[k] ?? '').join('|'),
  () => {
    syncFromRoute()
    selected.value = []
    store.fetchOrders(listParams.value)
  },
  { immediate: true },
)

// ── Estado → URL ──
function pushQuery(patch, { resetPage = true } = {}) {
  const q = { ...route.query, ...patch }
  if (resetPage && !('page' in patch)) q.page = 1
  Object.keys(q).forEach((k) => {
    if (q[k] === '' || q[k] === null || q[k] === undefined) delete q[k]
  })
  router.replace({ query: q })
}

// debounce simples de 400ms para os filtros reativos.
let debounceTimer = null
function debouncedPush(patch) {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => pushQuery(patch), 400)
}

function onFilterInput(key) {
  debouncedPush({ [key]: form[key] })
}

function clearFilters() {
  clearTimeout(debounceTimer)
  const q = {}
  if (route.query.order) q.order = route.query.order
  router.replace({ query: q })
}

const hasFilters = computed(() =>
  ['affiliate_id', 'status', 'date_from', 'date_to', 'min_value', 'max_value'].some((k) => form[k] !== ''),
)

// ── Ordenação ──
function onSort(col) {
  if (sortBy.value === col) {
    pushQuery({ sort_by: col, sort_dir: sortDir.value === 'asc' ? 'desc' : 'asc' })
  } else {
    pushQuery({ sort_by: col, sort_dir: 'asc' })
  }
}

// ── Paginação ──
function goToPage(p) {
  pushQuery({ page: p }, { resetPage: false })
}

const pageRange = computed(() => {
  const last = store.meta?.last_page || 1
  const current = store.meta?.current_page || 1
  const start = Math.max(1, current - 2)
  const end = Math.min(last, start + 4)
  const arr = []
  for (let i = start; i <= end; i++) arr.push(i)
  return arr
})

// ── Seleção múltipla ──
function toggle(id) {
  const i = selected.value.indexOf(id)
  i >= 0 ? selected.value.splice(i, 1) : selected.value.push(id)
}
function toggleAll() {
  const ids = store.orders.map((o) => o.id)
  const allSel = ids.every((id) => selected.value.includes(id))
  selected.value = allSel
    ? selected.value.filter((id) => !ids.includes(id))
    : [...new Set([...selected.value, ...ids])]
}

async function bulkCancel() {
  const ids = [...selected.value]
  let ok = 0
  let fail = 0
  for (const id of ids) {
    try {
      await store.updateStatus(id, 'cancelled')
      ok++
    } catch {
      fail++ // transição inválida (ex.: pedido não estava "pending")
    }
  }
  if (ok) toast.success(`${ok} pedido(s) cancelado(s).`)
  if (fail) toast.warning(`${fail} pedido(s) não puderam ser cancelados (status incompatível).`)
  selected.value = []
  store.fetchOrders(listParams.value)
}

// ── Drawer (deep-link via ?order=) ──
const drawerOrderId = computed(() => (route.query.order ? Number(route.query.order) : null))
const showDrawer = computed(() => drawerOrderId.value !== null)
function openOrder(o) {
  router.replace({ query: { ...route.query, order: o.id } })
}
function closeDrawer() {
  const q = { ...route.query }
  delete q.order
  router.replace({ query: q })
}
function onOrderUpdated() {
  store.fetchOrders(listParams.value)
}

onMounted(() => store.fetchAffiliates())
</script>

<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold" style="color: var(--color-text-primary)">Pedidos</h1>
      <p class="text-sm mt-0.5" style="color: var(--color-text-secondary)">Gestão de pedidos dos afiliados</p>
    </div>

    <!-- Filtros -->
    <div class="card p-4 mb-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div>
          <label class="block text-[11px] font-medium mb-1 uppercase tracking-wider" style="color: var(--color-text-secondary)">Afiliado</label>
          <select v-model="form.affiliate_id" @change="onFilterInput('affiliate_id')" class="w-full text-sm rounded-lg px-3 py-2" style="border: 1px solid var(--color-border)">
            <option value="">Todos</option>
            <option v-for="a in store.affiliates" :key="a.id" :value="a.id">{{ a.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-medium mb-1 uppercase tracking-wider" style="color: var(--color-text-secondary)">Status</label>
          <select v-model="form.status" @change="onFilterInput('status')" class="w-full text-sm rounded-lg px-3 py-2" style="border: 1px solid var(--color-border)">
            <option value="">Todos</option>
            <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
          </select>
        </div>
        <div>
          <label class="block text-[11px] font-medium mb-1 uppercase tracking-wider" style="color: var(--color-text-secondary)">De</label>
          <input type="date" v-model="form.date_from" @input="onFilterInput('date_from')" class="w-full text-sm rounded-lg px-3 py-2" style="border: 1px solid var(--color-border)" />
        </div>
        <div>
          <label class="block text-[11px] font-medium mb-1 uppercase tracking-wider" style="color: var(--color-text-secondary)">Até</label>
          <input type="date" v-model="form.date_to" @input="onFilterInput('date_to')" class="w-full text-sm rounded-lg px-3 py-2" style="border: 1px solid var(--color-border)" />
        </div>
        <div>
          <label class="block text-[11px] font-medium mb-1 uppercase tracking-wider" style="color: var(--color-text-secondary)">Valor mín.</label>
          <input type="number" min="0" step="0.01" v-model="form.min_value" @input="onFilterInput('min_value')" class="w-full text-sm rounded-lg px-3 py-2" style="border: 1px solid var(--color-border)" placeholder="0,00" />
        </div>
        <div>
          <label class="block text-[11px] font-medium mb-1 uppercase tracking-wider" style="color: var(--color-text-secondary)">Valor máx.</label>
          <input type="number" min="0" step="0.01" v-model="form.max_value" @input="onFilterInput('max_value')" class="w-full text-sm rounded-lg px-3 py-2" style="border: 1px solid var(--color-border)" placeholder="0,00" />
        </div>
        <div class="flex items-end">
          <button v-if="hasFilters" @click="clearFilters" class="btn-secondary btn-sm" aria-label="Limpar filtros">
            <X :size="14" /> Limpar
          </button>
        </div>
      </div>
    </div>

    <!-- Bulk toolbar -->
    <Transition enter-active-class="duration-150" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
      <div v-if="selected.length" class="card p-3 mb-4 flex items-center justify-between" style="border: 1px solid var(--color-primary)">
        <span class="text-sm font-medium" style="color: var(--color-text-primary)">{{ selected.length }} selecionado(s)</span>
        <button @click="bulkCancel" class="btn-secondary btn-sm" style="color: var(--color-danger)">
          <Ban :size="14" /> Cancelar selecionados
        </button>
      </div>
    </Transition>

    <!-- Tabela -->
    <OrdersTable
      :orders="store.orders"
      :loading="store.loading"
      :sort-by="sortBy"
      :sort-dir="sortDir"
      :selected="selected"
      @sort="onSort"
      @open="openOrder"
      @toggle="toggle"
      @toggle-all="toggleAll"
    />

    <!-- Paginação -->
    <div v-if="store.meta?.last_page > 1" class="flex items-center justify-between mt-4 flex-wrap gap-2">
      <span class="text-sm" style="color: var(--color-text-secondary)">
        Exibindo {{ store.meta.from }}–{{ store.meta.to }} de {{ store.meta.total }}
      </span>
      <div class="flex gap-1">
        <button
          v-for="p in pageRange"
          :key="p"
          @click="goToPage(p)"
          class="w-8 h-8 flex items-center justify-center text-sm rounded-md transition-colors"
          :style="{ background: p === store.meta.current_page ? 'var(--color-primary)' : 'transparent', color: p === store.meta.current_page ? '#fff' : 'var(--color-text-secondary)' }"
        >{{ p }}</button>
      </div>
    </div>

    <OrderDrawer :show="showDrawer" :order-id="drawerOrderId" @close="closeDrawer" @updated="onOrderUpdated" />
  </div>
</template>
