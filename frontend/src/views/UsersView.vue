<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUsersStore } from '../stores/users'
import { useAuthStore } from '../stores/auth'
import { useToast } from '../composables/useToast'
import UserTable from '../components/users/UserTable.vue'
import UserCreateModal from '../components/users/UserCreateModal.vue'
import UserEditModal from '../components/users/UserEditModal.vue'
import ConfirmDialog from '../components/ui/ConfirmDialog.vue'
import { Plus, Search } from 'lucide-vue-next'

const usersStore = useUsersStore()
const authStore = useAuthStore()
const toast = useToast()
const route = useRoute()
const router = useRouter()

const search = ref('')
const filterRole = ref('')
const filterStatus = ref('')
const page = ref(1)

const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingUser = ref(null)
const createModalRef = ref(null)
const editModalRef = ref(null)

const showConfirm = ref(false)
const confirmMsg = ref('')
const confirmAction = ref(null)

let searchTimer = null

async function load() {
  const params = { page: page.value }
  if (search.value) params.search = search.value
  if (filterRole.value) params.role = filterRole.value
  if (filterStatus.value) params.status = filterStatus.value
  await usersStore.fetch(params)
}

watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { page.value = 1; load() }, 300)
})

watch([filterRole, filterStatus], () => {
  page.value = 1
  load()
})

watch(page, load)

function openEdit(user) {
  editingUser.value = user
  showEditModal.value = true
}

function confirmToggle(user) {
  if (user.id === authStore.user?.id) {
    toast.warning('Você não pode alterar o status da sua própria conta.')
    return
  }
  confirmMsg.value = `Deseja ${user.is_active ? 'inativar' : 'ativar'} o usuário "${user.name}"?`
  confirmAction.value = async () => {
    try {
      await usersStore.toggleStatus(user.id)
      toast.success(`Usuário ${user.is_active ? 'inativado' : 'ativado'}.`)
      await load()
    } catch (e) {
      toast.error(e.response?.data?.errors?.is_active?.[0] || e.response?.data?.message || 'Erro ao alterar status.')
    }
  }
  showConfirm.value = true
}

async function execConfirm() {
  if (confirmAction.value) await confirmAction.value()
  showConfirm.value = false
}

async function handleCreate(payload) {
  try {
    await usersStore.create(payload)
    toast.success('Usuário criado. Informe as credenciais ao colaborador.')
    showCreateModal.value = false
    await load()
  } catch (e) {
    if (e.response?.status === 422) {
      createModalRef.value?.setErrors(e.response.data.errors)
    } else {
      toast.error(e.response?.data?.message || 'Erro ao criar usuário.')
    }
  }
}

async function handleUpdate(payload) {
  if (!editingUser.value) return
  try {
    await usersStore.update(editingUser.value.id, payload)
    toast.success('Usuário atualizado.')
    showEditModal.value = false
    await load()
  } catch (e) {
    if (e.response?.status === 422) {
      editModalRef.value?.setErrors(e.response.data.errors)
    } else {
      toast.error(e.response?.data?.message || 'Erro ao atualizar usuário.')
    }
  }
}

function checkNewQuery() {
  if (route.query.new === '1') {
    showCreateModal.value = true
    router.replace({ query: {} })
  }
}

watch(() => route.query.new, checkNewQuery)
onMounted(() => { load(); checkNewQuery() })
</script>

<template>
  <div>
    <div class="mb-6 flex items-end justify-between gap-3 flex-wrap">
      <div>
        <h1 class="text-2xl font-bold" style="color: var(--color-text-primary)">Usuários</h1>
        <p class="text-sm mt-0.5" style="color: var(--color-text-secondary)">Gerencie os usuários do sistema</p>
      </div>
      <button @click="showCreateModal = true" class="btn-primary btn-sm">
        <Plus :size="16" /> Novo Usuário
      </button>
    </div>

    <!-- Filters -->
    <div class="card p-4 mb-4 flex items-center gap-3 flex-wrap">
      <div class="relative flex-1 min-w-[240px]">
        <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--color-text-secondary); opacity: 0.5" />
        <input v-model="search" type="text" placeholder="Buscar por nome ou e-mail..." class="input-field" style="padding-left: 2.25rem" />
      </div>
      <select v-model="filterRole" class="input-field w-auto">
        <option value="">Todos os perfis</option>
        <option value="admin">Admin</option>
        <option value="operator">Operador</option>
      </select>
      <select v-model="filterStatus" class="input-field w-auto">
        <option value="">Todos os status</option>
        <option value="active">Ativo</option>
        <option value="inactive">Inativo</option>
      </select>
    </div>

    <UserTable
      :users="usersStore.users"
      :loading="usersStore.loading"
      :current-user-id="authStore.user?.id"
      @edit="openEdit"
      @toggle-status="confirmToggle"
    />

    <!-- Pagination -->
    <div v-if="usersStore.meta.last_page > 1" class="mt-4 flex items-center justify-between text-sm">
      <span style="color: var(--color-text-secondary)">
        {{ usersStore.users.length }} de {{ usersStore.meta.total }} usuários
      </span>
      <div class="flex items-center gap-2">
        <button @click="page--" :disabled="page <= 1" class="btn-secondary btn-sm">Anterior</button>
        <span style="color: var(--color-text-secondary)">
          Página {{ usersStore.meta.current_page }} de {{ usersStore.meta.last_page }}
        </span>
        <button @click="page++" :disabled="page >= usersStore.meta.last_page" class="btn-secondary btn-sm">Próxima</button>
      </div>
    </div>

    <UserCreateModal
      ref="createModalRef"
      :show="showCreateModal"
      @close="showCreateModal = false"
      @created="handleCreate"
    />

    <UserEditModal
      ref="editModalRef"
      :show="showEditModal"
      :user="editingUser"
      :is-self="editingUser?.id === authStore.user?.id"
      @close="showEditModal = false"
      @updated="handleUpdate"
    />

    <ConfirmDialog :show="showConfirm" :message="confirmMsg" @confirm="execConfirm" @cancel="showConfirm = false" />
  </div>
</template>
