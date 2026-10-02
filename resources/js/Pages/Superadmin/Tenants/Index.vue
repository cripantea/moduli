<script setup lang="ts">
import { ref, watch } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'

interface TenantRow {
  id: number
  name: string
  plan: 'trial' | 'pro' | 'enterprise'
  is_active: boolean
  users_count: number
  module_templates_count: number
  compiled_modules_count: number
  created_at: string
}

interface Paginated<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
  tenants: Paginated<TenantRow>
  filters: { search?: string }
}>()

// Search
const search = ref(props.filters.search ?? '')
let searchTimer: ReturnType<typeof setTimeout>
watch(search, (v) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    router.get(route('superadmin.tenants.index'), { search: v || undefined }, { preserveState: true, replace: true })
  }, 350)
})

// Create modal
const showCreate = ref(false)
const createForm = useForm({
  name: '',
  plan: 'pro' as 'trial' | 'pro' | 'enterprise',
  admin_name: '',
  admin_email: '',
  admin_password: '',
})

function submitCreate() {
  createForm.post(route('superadmin.tenants.store'), {
    onSuccess: () => {
      showCreate.value = false
      createForm.reset()
    },
  })
}

// Delete
function deleteTenant(id: number, name: string) {
  if (!confirm(`Eliminare il tenant "${name}"? L'azione non è reversibile.`)) return
  router.delete(route('superadmin.tenants.destroy', id))
}

function planLabel(plan: string) {
  return plan === 'trial' ? 'Trial' : plan === 'enterprise' ? 'Enterprise' : 'Pro'
}
function planColor(plan: string) {
  return plan === 'trial'
    ? 'bg-amber-50 text-amber-700 ring-amber-200'
    : plan === 'enterprise'
    ? 'bg-violet-50 text-violet-700 ring-violet-200'
    : 'bg-indigo-50 text-indigo-700 ring-indigo-200'
}
function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
  <Head title="Tenant — Superadmin" />

  <SuperadminLayout title="Tenant">

    <div class="p-6 max-w-6xl space-y-5">

      <!-- Toolbar -->
      <div class="flex items-center gap-3">
        <div class="relative flex-1 max-w-xs">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input
            v-model="search"
            type="text"
            placeholder="Cerca tenant…"
            class="w-full pl-9 pr-3 py-2 text-sm bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <span class="text-xs text-slate-400 ml-1">{{ tenants.total }} totali</span>
        <div class="flex-1"/>
        <button
          @click="showCreate = true"
          class="flex items-center gap-2 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
          </svg>
          Nuovo tenant
        </button>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-100 bg-slate-50/50">
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3">Nome</th>
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3">Piano</th>
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3 hidden sm:table-cell">Utenti</th>
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3 hidden md:table-cell">Template</th>
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3 hidden md:table-cell">Compilazioni</th>
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3 hidden lg:table-cell">Creato</th>
              <th class="text-left text-xs font-medium text-slate-500 px-4 py-3">Stato</th>
              <th class="px-4 py-3"/>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-if="!tenants.data.length">
              <td colspan="8" class="px-4 py-12 text-center text-slate-400 text-sm">Nessun tenant trovato.</td>
            </tr>
            <tr
              v-for="t in tenants.data"
              :key="t.id"
              class="hover:bg-slate-50/60 transition"
            >
              <td class="px-4 py-3 font-medium text-slate-900">{{ t.name }}</td>
              <td class="px-4 py-3">
                <span :class="['px-2 py-0.5 text-[11px] font-semibold rounded-md ring-1', planColor(t.plan)]">
                  {{ planLabel(t.plan) }}
                </span>
              </td>
              <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ t.users_count }}</td>
              <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ t.module_templates_count }}</td>
              <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ t.compiled_modules_count }}</td>
              <td class="px-4 py-3 text-slate-400 text-xs hidden lg:table-cell">{{ fmtDate(t.created_at) }}</td>
              <td class="px-4 py-3">
                <span :class="['inline-flex items-center gap-1 text-xs font-medium', t.is_active ? 'text-emerald-600' : 'text-slate-400']">
                  <span :class="['w-1.5 h-1.5 rounded-full', t.is_active ? 'bg-emerald-400' : 'bg-slate-300']"></span>
                  {{ t.is_active ? 'Attivo' : 'Inattivo' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-3 justify-end">
                  <Link
                    :href="route('superadmin.tenants.edit', t.id)"
                    class="text-xs text-slate-400 hover:text-indigo-600 transition"
                  >
                    Modifica
                  </Link>
                  <button
                    @click="deleteTenant(t.id, t.name)"
                    class="text-xs text-slate-300 hover:text-red-500 transition"
                  >
                    Elimina
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="tenants.last_page > 1" class="flex items-center gap-1 justify-center">
        <template v-for="link in tenants.links" :key="link.label">
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
      <Transition
        enter-active-class="transition"
        enter-from-class="opacity-0"
        leave-active-class="transition"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showCreate"
          class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4"
          @click.self="showCreate = false"
        >
          <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-5">
            <div class="flex items-center justify-between">
              <h2 class="text-base font-semibold text-slate-800">Nuovo tenant</h2>
              <button @click="showCreate = false" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <form @submit.prevent="submitCreate" class="space-y-4">
              <!-- Tenant name -->
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Nome azienda *</label>
                <input
                  v-model="createForm.name"
                  type="text"
                  required
                  placeholder="Es. Studio Legale Rossi"
                  class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-500">{{ createForm.errors.name }}</p>
              </div>

              <!-- Plan -->
              <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Piano</label>
                <select
                  v-model="createForm.plan"
                  class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
                >
                  <option value="trial">Trial</option>
                  <option value="pro">Pro</option>
                  <option value="enterprise">Enterprise</option>
                </select>
              </div>

              <!-- Optional first admin -->
              <div class="pt-2 border-t border-slate-100 space-y-3">
                <p class="text-xs text-slate-400">Opzionale: crea il primo amministratore</p>
                <div>
                  <label class="block text-xs font-medium text-slate-600 mb-1">Nome admin</label>
                  <input
                    v-model="createForm.admin_name"
                    type="text"
                    placeholder="Mario Rossi"
                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 mb-1">Email admin</label>
                  <input
                    v-model="createForm.admin_email"
                    type="email"
                    placeholder="mario@azienda.it"
                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                  <p v-if="createForm.errors.admin_email" class="mt-1 text-xs text-red-500">{{ createForm.errors.admin_email }}</p>
                </div>
                <div>
                  <label class="block text-xs font-medium text-slate-600 mb-1">Password admin</label>
                  <input
                    v-model="createForm.admin_password"
                    type="password"
                    placeholder="min. 8 caratteri"
                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  />
                  <p v-if="createForm.errors.admin_password" class="mt-1 text-xs text-red-500">{{ createForm.errors.admin_password }}</p>
                </div>
              </div>

              <div class="flex gap-3 pt-1">
                <button
                  type="button"
                  @click="showCreate = false"
                  class="flex-1 py-2 text-sm text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition"
                >
                  Annulla
                </button>
                <button
                  type="submit"
                  :disabled="createForm.processing"
                  class="flex-1 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 rounded-lg transition font-medium"
                >
                  Crea tenant
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

  </SuperadminLayout>
</template>
