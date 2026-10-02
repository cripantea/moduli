<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'
import type { Tenant, User } from '@/types'

interface TenantWithUsers extends Tenant {
  users?: User[]
}

const props = defineProps<{
  tenant: TenantWithUsers
  stats: { templates: number; compiled: number }
}>()

const form = useForm({
  name: props.tenant.name,
  plan: props.tenant.plan,
  is_active: props.tenant.is_active,
})

function submit() {
  form.put(route('superadmin.tenants.update', props.tenant.id))
}

function planLabel(plan: string) {
  return plan === 'trial' ? 'Trial' : plan === 'enterprise' ? 'Enterprise' : 'Pro'
}
</script>

<template>
  <Head :title="`Modifica ${tenant.name} — Superadmin`" />

  <SuperadminLayout :title="`Tenant: ${tenant.name}`">
    <div class="p-6 max-w-2xl space-y-6">

      <!-- Stats strip -->
      <div class="grid grid-cols-3 gap-3">
        <div v-for="item in [
          { label: 'Utenti', value: tenant.users?.length ?? 0 },
          { label: 'Template', value: stats.templates },
          { label: 'Compilazioni', value: stats.compiled },
        ]" :key="item.label"
          class="bg-white rounded-xl border border-slate-200 px-4 py-3 text-center"
        >
          <p class="text-xl font-bold text-slate-900">{{ item.value }}</p>
          <p class="text-xs text-slate-400 mt-0.5">{{ item.label }}</p>
        </div>
      </div>

      <!-- Edit form -->
      <div class="bg-white rounded-xl border border-slate-200 p-6">
        <form @submit.prevent="submit" class="space-y-4">

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Nome azienda</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Piano</label>
            <select
              v-model="form.plan"
              class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
            >
              <option value="trial">Trial</option>
              <option value="pro">Pro</option>
              <option value="enterprise">Enterprise</option>
            </select>
          </div>

          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="form.is_active = !form.is_active"
              :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
                form.is_active ? 'bg-indigo-600' : 'bg-slate-200'
              ]"
            >
              <span :class="['inline-block h-4 w-4 rounded-full bg-white shadow transition-transform', form.is_active ? 'translate-x-6' : 'translate-x-1']"/>
            </button>
            <span class="text-sm text-slate-700">Tenant attivo</span>
          </div>

          <div class="flex gap-3 pt-2">
            <a
              :href="route('superadmin.tenants.index')"
              class="flex-1 py-2 text-sm text-center text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition"
            >
              Annulla
            </a>
            <button
              type="submit"
              :disabled="form.processing"
              class="flex-1 py-2 text-sm text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 rounded-lg transition font-medium"
            >
              Salva modifiche
            </button>
          </div>

        </form>
      </div>

      <!-- Users list (read-only overview) -->
      <div v-if="tenant.users && tenant.users.length" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100">
          <h2 class="text-sm font-semibold text-slate-700">Utenti del tenant</h2>
        </div>
        <div class="divide-y divide-slate-50">
          <div
            v-for="u in tenant.users"
            :key="u.id"
            class="flex items-center gap-3 px-5 py-2.5"
          >
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-slate-800 truncate">{{ u.name }}</p>
              <p class="text-xs text-slate-400 truncate">{{ u.email }}</p>
            </div>
            <span class="text-xs text-slate-400">{{ u.role }}</span>
            <span :class="['w-1.5 h-1.5 rounded-full shrink-0', u.is_active ? 'bg-emerald-400' : 'bg-slate-300']"/>
          </div>
        </div>
      </div>

    </div>
  </SuperadminLayout>
</template>
