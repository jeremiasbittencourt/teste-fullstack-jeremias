<script setup>
import { Pencil, Search, Clock } from 'lucide-vue-next'
import BaseToggle from '../ui/BaseToggle.vue'
import UserRoleBadge from './UserRoleBadge.vue'

defineProps({
  users: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  currentUserId: { type: Number, default: null },
})
defineEmits(['edit', 'toggle-status'])

function fmtDate(iso) {
  if (!iso) return '-'
  const d = new Date(iso)
  return d.toLocaleDateString('pt-BR')
}

function fmtDateTime(iso) {
  if (!iso) return null
  const d = new Date(iso)
  return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div class="card overflow-hidden">
    <table class="min-w-full">
      <thead>
        <tr style="background: var(--color-bg-page)">
          <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--color-text-secondary)">Nome</th>
          <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--color-text-secondary)">E-mail</th>
          <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--color-text-secondary)">Perfil</th>
          <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--color-text-secondary)">Status</th>
          <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--color-text-secondary)">Criado em</th>
          <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--color-text-secondary)">Último acesso</th>
          <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider" style="color: var(--color-text-secondary)">Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="loading">
          <td colspan="7" class="px-4 py-8 text-center text-sm" style="color: var(--color-text-secondary)">Carregando...</td>
        </tr>
        <tr v-else-if="!users.length">
          <td colspan="7" class="px-4 py-8 text-center text-sm" style="color: var(--color-text-secondary)">
            <Search :size="20" class="inline-block mb-2 opacity-40" /><br />
            Nenhum usuário encontrado.
          </td>
        </tr>
        <tr
          v-for="user in users"
          :key="user.id"
          class="transition-colors hover:bg-[var(--color-primary-light)]"
          :class="{ 'opacity-50': !user.is_active }"
          style="border-top: 1px solid var(--color-border)"
        >
          <td class="px-4 py-3 text-[13px] font-medium" style="color: var(--color-text-primary)">
            <div class="flex items-center gap-2">
              {{ user.name }}
              <Clock v-if="!user.last_login_at" :size="12" :title="'Nunca acessou'" style="color: var(--color-warning)" />
            </div>
          </td>
          <td class="px-4 py-3 text-[13px]" style="color: var(--color-text-secondary)">{{ user.email }}</td>
          <td class="px-4 py-3"><UserRoleBadge :role="user.role" /></td>
          <td class="px-4 py-3">
            <BaseToggle
              :model-value="user.is_active"
              :disabled="user.id === currentUserId"
              @update:model-value="$emit('toggle-status', user)"
            />
          </td>
          <td class="px-4 py-3 text-[13px]" style="color: var(--color-text-secondary)">{{ fmtDate(user.created_at) }}</td>
          <td class="px-4 py-3 text-[13px]" style="color: var(--color-text-secondary)">
            <span v-if="user.last_login_at">{{ fmtDateTime(user.last_login_at) }}</span>
            <span v-else style="color: var(--color-text-secondary); opacity: 0.6">Nunca acessou</span>
          </td>
          <td class="px-4 py-3 text-right">
            <button @click="$emit('edit', user)" class="btn-ghost btn-sm">
              <Pencil :size="14" /> Editar
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
