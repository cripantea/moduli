<script setup lang="ts">
import { ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'
import type { Tenant } from '@/types'

interface UserRow {
  id: number
  name: string
  email: string
  role: 'superadmin' | 'admin' | 'user'
  is_active: boolean
  tenant_id: number | null
  tenant?: Tenant
  created_at: string
}

interface Paginated<T> {
  data: T[]
  current_page: number
  last_page: number
  total: number
  links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
  users: Paginated<UserRow>
  tenants: Tenant[]
  filters: { search?: string; role?: string; tenant_id?: string }
}>()

// Filters
const search = ref(props.filters.search ?? '')
const role = ref(props.filters.role ?? '')
const tenantId = ref(props.filters.tenant_id ?? '')

function applyFilters() {
  router.get(
    route('superadmin.users'),
    {
      search: search.value || undefined,
      role: role.value || undefined,
      tenant_id: tenantId.value || undefined,
    },
    { preserveState: true, replace: true },
  )
}

let filterTimer: ReturnType<typeof setTimeout>
watch([search, role, tenantId], () => {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(applyFilters, 350)
})

// Create modal
const showCreate = ref(false)
const createForm = useForm({
  name: '',
  email: '',
  password: '',
  role: 'user' as 'admin' | 'user',
  tenant_id: '' as string | number,
})

function submitCreate() {
  createForm.post(route('superadmin.users.store'), {
    onSuccess: () => {
      showCreate.value = false
      createForm.reset()
    },
  })
}

// Edit modal
const editTarget = ref<UserRow | null>(null)
const editForm = useForm({
  name: '',
  email: '',
  role: 'user' as 'superadmin' | 'admin' | 'user',
  tenant_id: '' as string | number,
})

function openEdit(u: UserRow) {
  editTarget.value = u
  editForm.name = u.name
  editForm.email = u.email
  editForm.role = u.role
  editForm.tenant_id = u.tenant_id ?? ''
}

function submitEdit() {
  if (!editTarget.value) return
  editForm.put(route('superadmin.users.update', editTarget.value.id), {
    onSuccess: () => { editTarget.value = null },
  })
}

// Toggle active
function toggleUser(u: UserRow) {
  router.post(route('superadmin.users.toggle', u.id), {}, { preserveScroll: true })
}

function roleBadge(r: string) {
  if (r === 'superadmin') return 'bg-red-50 text-red-700 ring-red-200'
  if (r === 'admin') return 'bg-indigo-50 text-indigo-700 ring-indigo-200'
  return 'bg-slate-50 text-slate-600 ring-slate-200'
}
function roleLabel(r: string) {
  if (r === 'superadmin') return 'Superadmin'
  if (r === 'admin') return 'Admin'
  return 'Utente'
}
function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
  <Head title="Utenti — Superadmin" />

  <SuperadminLayout title="Utenti">

    <div class="p-6 max-w-6xl space-y-5">

      <!-- Toolbar -->
      <div class="flex flex-wrap items-center gap-3">
        <!-- Search -->
        <div class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input
            v-model="search"
            type="text"
            placeholder="Nome o email…"
            class="pl-9 pr-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 w-52"
          />
        </div>

        <!-- Role filter -->
        <select
          v-model="role"
          class="py-2 px-3 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-600"
        >
          <option value="">Tutti i ruoli</option>
          <option value="superadmin">Superadmin</option>
          <option value="admin">Admin</option>
          <option value="user">Utente</option>
        </select>

        <!-- Tenant filter -->
        <select
          v-model="tenantId"
          class="py-2 px-3 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-slate-600"
        >
          <option value="">Tutti i tenant</option>
          <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>

        <span class="text-xs text-slate-400">{{ users.total }} totali</span>
        <div class="flex-1"/>

        <button
          @click="showCreate = true"
          class="flex items-center gap-2 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
          </svg>
          Nuovo utente
        </button>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/50">
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3">Utente</th>
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3">Ruolo</th>
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3 hidden md:table-cell">Tenant</th>
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3 hidden lg:table-cell">Iscritto</th>
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3">Stato</th>
              <th class="px-4 py-3"/>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-if="!users.data.length">
              <td colspan="6" class="px-4 py-12 text-center text-slate-400 text-sm">Nessun utente trovato.</td>
            </tr>
            <tr
              v-for="u in users.data"
              :key="u.id"
              class="hover:bg-slate-50/60 transition"
            >
              <td class="px-4 py-3">
                <div class="font-medium text-slate-900">{{ u.name }}</div>
                <div class="text-xs text-slate-400">{{ u.email }}</div>
              </td>
              <td class="px-4 py-3">
                <span :class="['px-2 py-0.5 text-[11px] font-semibold rounded-md ring-1', roleBadge(u.role)]">
                  {{ roleLabel(u.role) }}
                </span>
              </td>
              <td class="px-4 py-3 text-slate-500 hidden md:table-cell">
                {{ u.tenant?.name ?? '—' }}
              </td>
              <td class="px-4 py-3 text-slate-400 text-xs hidden lg:table-cell">{{ fmtDate(u.created_at) }}</td>
              <td class="px-4 py-3">
                <button
                  @click="toggleUser(u)"
                  :class="[
                    'inline-flex items-center gap-1 text-xs font-medium transition',
                    u.is_active ? 'text-emerald-600 hover:text-red-500' : 'text-slate-400 hover:text-emerald-500'
                  ]"
                  :title="u.is_active ? 'Clicca per disabilitare' : 'Clicca per abilitare'"
                >
                  <span :class="['w-1.5 h-1.5 rounded-full', u.is_active ? 'bg-emerald-400' : 'bg-slate-300']"></span>
                  {{ u.is_active ? 'Attivo' : 'Inattivo' }}
                </button>
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  @click="openEdit(u)"
                  class="text-xs text-slate-400 hover:text-indigo-600 transition"
                >
                  Modifica
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="users.last_page > 1" class="flex items-center gap-1 justify-center">
        <template v-for="link in users.links" :key="link.label">
          <component
            :is="link.url ? Link : 'span'"
            :href="link.url ?? undefined"
            :class="[
              'px-3 py-1.5 text-xs rounded-lg transition',
              link.active
                ? 'bg-indigo-600 text-white font-semibold'
                : link.url
                ? 'text-slate-600 hover:bg-slate-100'
                : 'text-slate-300 cursor-default'
            ]"
            v-html="link.label"
          />
        </template>
      </div>

    </div>

    <!-- ── Create modal ── -->
    <Teleport to="body">
      <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
        <div
          v-if="showCreate"
          class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
          @click.self="showCreate = false"
        >
          <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-5">
            <div class="flex items-center justify-between">
              <h2 class="text-base font-semibold text-slate-800">Nuovo utente</h2>
              <button @click="showCreate = false" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
            <form @submit.prevent="submitCreate" class="space-y-4">
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Nome *</label>
                <input v-model="createForm.name" type="text" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-500">{{ createForm.errors.name }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Email *</label>
                <input v-model="createForm.email" type="email" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <p v-if="createForm.errors.email" class="mt-1 text-xs text-red-500">{{ createForm.errors.email }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Password *</label>
                <input v-model="createForm.password" type="password" required placeholder="min. 8 caratteri" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <p v-if="createForm.errors.password" class="mt-1 text-xs text-red-500">{{ createForm.errors.password }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Ruolo</label>
                <select v-model="createForm.role" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                  <option value="user">Utente</option>
                  <option value="admin">Admin</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Tenant</label>
                <select v-model="createForm.tenant_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                  <option value="">Nessun tenant</option>
                  <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
                <p v-if="createForm.errors.tenant_id" class="mt-1 text-xs text-red-500">{{ createForm.errors.tenant_id }}</p>
              </div>
              <div class="flex gap-3 pt-1">
                <button type="button" @click="showCreate = false" class="flex-1 py-2 text-sm text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Annulla</button>
                <button type="submit" :disabled="createForm.processing" class="flex-1 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 rounded-lg transition font-medium">Crea utente</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ── Edit modal ── -->
    <Teleport to="body">
      <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
        <div
          v-if="editTarget"
          class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
          @click.self="editTarget = null"
        >
          <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-5">
            <div class="flex items-center justify-between">
              <h2 class="text-base font-semibold text-slate-800">Modifica utente</h2>
              <button @click="editTarget = null" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
            <form @submit.prevent="submitEdit" class="space-y-4">
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Nome</label>
                <input v-model="editForm.name" type="text" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <p v-if="editForm.errors.name" class="mt-1 text-xs text-red-500">{{ editForm.errors.name }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Email</label>
                <input v-model="editForm.email" type="email" required class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                <p v-if="editForm.errors.email" class="mt-1 text-xs text-red-500">{{ editForm.errors.email }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Ruolo</label>
                <select v-model="editForm.role" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                  <option value="user">Utente</option>
                  <option value="admin">Admin</option>
                  <option value="superadmin">Superadmin</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Tenant</label>
                <select v-model="editForm.tenant_id" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                  <option value="">Nessun tenant</option>
                  <option v-for="t in tenants" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
              </div>
              <div class="flex gap-3 pt-1">
                <button type="button" @click="editTarget = null" class="flex-1 py-2 text-sm text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">Annulla</button>
                <button type="submit" :disabled="editForm.processing" class="flex-1 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 rounded-lg transition font-medium">Salva</button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

  </SuperadminLayout>
</template>
