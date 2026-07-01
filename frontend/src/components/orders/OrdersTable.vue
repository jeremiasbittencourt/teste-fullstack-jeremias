<script setup>
import { computed } from 'vue'
import { ChevronUp, ChevronDown, PackageOpen } from 'lucide-vue-next'
import BaseSkeleton from '../ui/BaseSkeleton.vue'
import OrderStatusBadge from './OrderStatusBadge.vue'

const props = defineProps({
  orders: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  sortBy: { type: String, default: 'created_at' },
  sortDir: { type: String, default: 'desc' },
  selected: { type: Array, default: () => [] },
})

const emit = defineEmits(['sort', 'open', 'toggle', 'toggle-all'])

const columns = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'affiliate', label: 'Afiliado', sortable: false },
  { key: 'total', label: 'Valor', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'created_at', label: 'Data', sortable: true },
]

const selectedSet = computed(() => new Set(props.selected))
const allSelected = computed(() => props.orders.length > 0 && props.orders.every(o => selectedSet.value.has(o.id)))

function money(v) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(v || 0))
}
function date(v) {
  return v ? new Date(v).toLocaleDateString('pt-BR') : '—'
}
</script>

<template>
  <BaseSkeleton v-if="loading" type="table" :lines="6" />

  <template v-else-if="orders.length">
    <!-- Desktop -->
    <div class="hidden md:block overflow-x-auto card p-0">
      <table class="min-w-full">
        <thead>
          <tr style="background: var(--color-bg-page)">
            <th class="w-10 px-3 py-3">
              <input type="checkbox" :checked="allSelected" @change="emit('toggle-all')" aria-label="Selecionar todos" />
            </th>
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider select-none"
              :class="col.sortable ? 'cursor-pointer' : ''"
              style="color: var(--color-text-secondary)"
              @click="col.sortable && emit('sort', col.key)"
            >
              <span class="inline-flex items-center gap-1">
                {{ col.label }}
                <template v-if="col.sortable && sortBy === col.key">
                  <ChevronUp v-if="sortDir === 'asc'" :size="13" />
                  <ChevronDown v-else :size="13" />
                </template>
              </span>
            </th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(o, i) in orders"
            :key="o.id"
            class="transition-colors duration-100 hover:bg-[var(--color-primary-light)] cursor-pointer"
            :style="{ borderTop: '1px solid var(--color-border)', background: i % 2 ? 'rgba(0,0,0,0.01)' : 'transparent' }"
            @click="emit('open', o)"
          >
            <td class="w-10 px-3 py-3" @click.stop>
              <input type="checkbox" :checked="selectedSet.has(o.id)" @change="emit('toggle', o.id)" :aria-label="`Selecionar pedido ${o.id}`" />
            </td>
            <td class="px-4 py-3 text-[13px] font-mono" style="color: var(--color-text-primary)">#{{ o.id }}</td>
            <td class="px-4 py-3 text-[13px]" style="color: var(--color-text-primary)">{{ o.affiliate?.name || '—' }}</td>
            <td class="px-4 py-3 text-[13px] font-medium" style="color: var(--color-text-primary)">{{ money(o.total) }}</td>
            <td class="px-4 py-3"><OrderStatusBadge :status="o.status" :label="o.status_label" /></td>
            <td class="px-4 py-3 text-[13px]" style="color: var(--color-text-secondary)">{{ date(o.created_at) }}</td>
            <td class="px-4 py-3 text-right">
              <button class="text-xs font-medium hover:underline" style="color: var(--color-primary)" @click.stop="emit('open', o)">Ver</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile: lista de cards -->
    <div class="md:hidden space-y-3">
      <div
        v-for="o in orders"
        :key="o.id"
        class="card p-4 cursor-pointer"
        @click="emit('open', o)"
      >
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <input type="checkbox" :checked="selectedSet.has(o.id)" @click.stop @change="emit('toggle', o.id)" :aria-label="`Selecionar pedido ${o.id}`" />
            <span class="font-mono text-sm font-semibold" style="color: var(--color-text-primary)">#{{ o.id }}</span>
          </div>
          <OrderStatusBadge :status="o.status" :label="o.status_label" />
        </div>
        <div class="flex items-center justify-between text-sm">
          <span style="color: var(--color-text-secondary)">{{ o.affiliate?.name || '—' }}</span>
          <span class="font-medium" style="color: var(--color-text-primary)">{{ money(o.total) }}</span>
        </div>
        <p class="text-xs mt-1" style="color: var(--color-text-secondary)">{{ date(o.created_at) }}</p>
      </div>
    </div>
  </template>

  <!-- Empty state -->
  <div v-else class="card p-12 text-center">
    <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center" style="background: var(--color-bg-page)">
      <PackageOpen :size="30" style="color: var(--color-text-secondary); opacity: 0.5" />
    </div>
    <p class="text-sm font-medium" style="color: var(--color-text-secondary)">Nenhum pedido encontrado para os filtros aplicados.</p>
  </div>
</template>
