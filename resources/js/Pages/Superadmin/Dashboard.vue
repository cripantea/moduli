<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'

interface TenantRow {
  id: number
  name: string
  plan: string
  is_active: boolean
  users_count: number
  module_templates_count: number
  compiled_modules_count: number
  created_at: string
}

defineProps<{
  stats: { tenants: number; users: number; templates: number; compiled: number }
  recentTenants: TenantRow[]
}>()

function planLabel(plan: string) {
  return plan === 'trial' ? 'Trial' : plan === 'enterprise' ? 'Enterprise' : 'Pro'
}
function planColor(plan: string) {
  return plan === 'trial'
    ? 'bg-amber-50 text-amber-700'
    : plan === 'enterprise'
    ? 'bg-violet-50 text-violet-700'
    : 'bg-indigo-50 text-indigo-700'
}
function fmtDate(iso: string) {
  return new Date(iso).toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
  <Head title="Dashboard — Superadmin" />

  <SuperadminLayout title="Dashboard">
    <div class="p-6 space-y-8 max-w-6xl">

      <!-- Stat cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="card in [
          { label: 'Tenant attivi', value: stats.tenants, icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', color: 'text-indigo-600 bg-indigo-50' },
          { label: 'Utenti totali', value: stats.users, icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', color: 'text-blue-600 bg-blue-50' },
          { label: 'Template', value: stats.templates, icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z', color: 'text-emerald-600 bg-emerald-50' },
          { label: 'Compilazioni', value: stats.compiled, icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', color: 'text-orange-600 bg-orange-50' },
        ]" :key="card.label"
          class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4"
        >
          <div :class="['w-10 h-10 rounded-xl flex items-center justify-center shrink-0', card.color]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="card.icon"/>
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-slate-900">{{ card.value.toLocaleString('it-IT') }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ card.label }}</p>
          </div>
        </div>
      </div>

      <!-- Recent tenants -->
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
          <h2 class="text-sm font-semibold text-slate-800">Tenant recenti</h2>
          <Link :href="route('superadmin.tenants.index')" class="text-xs text-indigo-600 hover:underline">
            Vedi tutti →
          </Link>
        </div>

        <div v-if="recentTenants.length" class="divide-y divide-slate-50">
          <div
            v-for="t in recentTenants"
            :key="t.id"
            class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/60 transition"
          >
            <!-- Name + plan -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-sm font-medium text-slate-900 truncate">{{ t.name }}</p>
                <span :class="['px-1.5 py-0.5 text-[10px] font-semibold rounded-md', planColor(t.plan)]">
                  {{ planLabel(t.plan) }}
                </span>
              </div>
              <p class="text-[11px] text-slate-400 mt-0.5">Creato {{ fmtDate(t.created_at) }}</p>
            </div>

            <!-- Counts -->
            <div class="hidden sm:flex items-center gap-6 text-xs text-slate-500">
              <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                {{ t.users_count }}
              </span>
              <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
                </svg>
                {{ t.module_templates_count }} template
              </span>
              <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ t.compiled_modules_count }} compilazioni
              </span>
            </div>

            <!-- Status dot -->
            <span :class="['w-2 h-2 rounded-full shrink-0', t.is_active ? 'bg-emerald-400' : 'bg-slate-300']" :title="t.is_active ? 'Attivo' : 'Disabilitato'"></span>

            <!-- Edit link -->
            <Link
              :href="route('superadmin.tenants.edit', t.id)"
              class="text-xs text-slate-400 hover:text-indigo-600 transition shrink-0"
            >
              Modifica
            </Link>
          </div>
        </div>

        <div v-else class="px-5 py-12 text-center text-sm text-slate-400">
          Nessun tenant ancora.
          <Link :href="route('superadmin.tenants.index')" class="text-indigo-500 hover:underline ml-1">Crea il primo</Link>.
        </div>
      </div>

    </div>
  </SuperadminLayout>
</template>
