<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'

interface Ticket {
  id: number
  subject: string
  message: string
  page: string | null
  status: 'aperto' | 'chiuso'
  created_at: string
  tenant: { id: number; name: string } | null
  user: { id: number; name: string; email: string } | null
}

defineProps<{
  tickets: { data: Ticket[]; links: { url: string | null; label: string; active: boolean }[]; last_page: number }
  stato: string
  aperti: number
}>()

function setStatus(ticket: Ticket, status: 'aperto' | 'chiuso') {
  router.put(route('superadmin.tickets.update', ticket.id), { status }, { preserveScroll: true })
}

const date = (iso: string) => new Date(iso).toLocaleString('it-IT', { dateStyle: 'short', timeStyle: 'short' })
</script>

<template>
  <Head title="Assistenza — Superadmin" />

  <SuperadminLayout title="Assistenza">
    <div class="p-6 max-w-4xl space-y-4">
      <div class="flex gap-2 text-sm">
        <Link
          v-for="s in [{ key: 'aperto', label: `Aperti (${aperti})` }, { key: 'chiuso', label: 'Chiusi' }, { key: 'tutti', label: 'Tutti' }]"
          :key="s.key"
          :href="route('superadmin.tickets.index', { stato: s.key })"
          class="px-3 py-1.5 rounded-lg border"
          :class="stato === s.key ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
        >{{ s.label }}</Link>
      </div>

      <p v-if="!tickets.data.length" class="text-sm text-slate-500 py-10 text-center">Nessuna richiesta.</p>

      <article v-for="t in tickets.data" :key="t.id" class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-start justify-between gap-4">
          <div class="min-w-0">
            <h2 class="text-sm font-semibold text-slate-900">{{ t.subject }}</h2>
            <p class="mt-0.5 text-xs text-slate-500">
              {{ date(t.created_at) }} · {{ t.tenant?.name ?? '—' }} ·
              <a v-if="t.user" :href="`mailto:${t.user.email}?subject=${encodeURIComponent('Re: ' + t.subject)}`" class="text-indigo-600 hover:underline">{{ t.user.name }} &lt;{{ t.user.email }}&gt;</a>
              <span v-if="t.page"> · da {{ t.page }}</span>
            </p>
          </div>
          <button
            type="button"
            class="shrink-0 px-3 py-1 rounded-lg text-xs font-medium border"
            :class="t.status === 'aperto' ? 'border-green-300 text-green-700 hover:bg-green-50' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
            @click="setStatus(t, t.status === 'aperto' ? 'chiuso' : 'aperto')"
          >{{ t.status === 'aperto' ? 'Segna come risolto' : 'Riapri' }}</button>
        </div>
        <p class="mt-3 text-sm text-slate-700 whitespace-pre-line">{{ t.message }}</p>
      </article>

      <div v-if="tickets.last_page > 1" class="flex gap-1">
        <Link
          v-for="l in tickets.links"
          :key="l.label"
          :href="l.url ?? ''"
          class="px-2.5 py-1 text-xs rounded border"
          :class="[l.active ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-200 text-slate-600', !l.url && 'pointer-events-none opacity-40']"
          v-html="l.label"
        />
      </div>
    </div>
  </SuperadminLayout>
</template>
