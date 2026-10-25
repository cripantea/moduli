<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'
import { computed } from 'vue'

interface Step { key: string; label: string; total: number; dalPrima: number | null }

const props = defineProps<{
  steps: Step[]
  giorni: ({ day: string } & Record<string, number | string>)[]
  filtri: { giorni: number; fonte: string }
  clarity: string | null
}>()

const top = computed(() => Math.max(1, ...props.steps.map((s) => s.total)))
const periodi = [{ v: 1, l: 'Oggi' }, { v: 7, l: '7 giorni' }, { v: 14, l: '14 giorni' }, { v: 30, l: '30 giorni' }]
const fonti = [{ v: 'tutte', l: 'Tutte le fonti' }, { v: 'meta', l: 'Pubblicità Meta' }, { v: 'altro', l: 'Altro' }]
const day = (iso: string) => new Date(iso).toLocaleDateString('it-IT', { weekday: 'short', day: 'numeric', month: 'short' })
</script>

<template>
  <Head title="Funnel — Superadmin" />

  <SuperadminLayout title="Funnel">
    <div class="p-6 max-w-4xl space-y-6">
      <div class="flex flex-wrap items-center gap-2 text-sm">
        <Link
          v-for="p in periodi"
          :key="p.v"
          :href="route('superadmin.funnel', { giorni: p.v, fonte: filtri.fonte })"
          class="px-3 py-1.5 rounded-lg border"
          :class="filtri.giorni === p.v ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
        >{{ p.l }}</Link>
        <span class="mx-1 text-slate-300">|</span>
        <Link
          v-for="f in fonti"
          :key="f.v"
          :href="route('superadmin.funnel', { giorni: filtri.giorni, fonte: f.v })"
          class="px-3 py-1.5 rounded-lg border"
          :class="filtri.fonte === f.v ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
        >{{ f.l }}</Link>
        <a v-if="clarity" :href="clarity" target="_blank" rel="noopener" class="ml-auto text-indigo-600 hover:underline">Mappe di calore e registrazioni (Clarity) →</a>
      </div>

      <section class="bg-white rounded-xl border border-slate-200 p-5">
        <h2 class="text-sm font-semibold text-slate-900">Percorso</h2>
        <p class="mt-0.5 text-xs text-slate-500">Persone che fanno ogni passo (una volta per visita). La percentuale è quanti arrivano dal passo prima.</p>
        <ol class="mt-4 space-y-3">
          <li v-for="s in steps" :key="s.key">
            <div class="flex items-baseline justify-between text-sm">
              <span class="text-slate-700">{{ s.label }}</span>
              <span class="tabular-nums">
                <strong class="text-slate-900">{{ s.total }}</strong>
                <span v-if="s.dalPrima !== null" class="ml-2 text-xs" :class="s.dalPrima < 20 ? 'text-red-600' : 'text-slate-500'">{{ s.dalPrima }}%</span>
              </span>
            </div>
            <div class="mt-1 h-2 rounded-full bg-slate-100">
              <div class="h-2 rounded-full bg-indigo-500" :style="{ width: `${(s.total / top) * 100}%` }" />
            </div>
          </li>
        </ol>
      </section>

      <section class="bg-white rounded-xl border border-slate-200 overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-slate-500 border-b border-slate-100">
              <th class="px-4 py-2 font-medium">Giorno</th>
              <th v-for="s in steps" :key="s.key" class="px-3 py-2 font-medium text-right whitespace-nowrap">{{ s.label }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!giorni.length"><td :colspan="steps.length + 1" class="px-4 py-8 text-center text-slate-500">Nessun dato nel periodo.</td></tr>
            <tr v-for="g in giorni" :key="g.day" class="border-b border-slate-50">
              <td class="px-4 py-2 whitespace-nowrap text-slate-700">{{ day(g.day) }}</td>
              <td v-for="s in steps" :key="s.key" class="px-3 py-2 text-right tabular-nums">{{ g[s.key] ?? 0 }}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </SuperadminLayout>
</template>
