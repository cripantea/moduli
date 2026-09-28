<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

interface CompiledItem {
  id: number
  template_name: string
  original_filename: string
  created_at: string
  values: Record<string, string> | null
}

interface Paginator {
  data: CompiledItem[]
  links: { url: string | null; label: string; active: boolean }[]
  from: number | null
  to: number | null
  total: number
}

const props = defineProps<{
  compiled: Paginator
  filters: { search: string }
}>()

const search = ref(props.filters.search)
let timer: ReturnType<typeof setTimeout>

watch(search, (val) => {
  clearTimeout(timer)
  timer = setTimeout(() => {
    router.get(route('compiled.index'), { search: val }, { preserveState: true, replace: true })
  }, 350)
})

function destroy(id: number) {
  if (!confirm('Eliminare questa compilazione?')) return
  router.delete(route('compiled.destroy', id))
}

// Extract a human-readable client name from values
function clientName(values: Record<string, string> | null): string {
  if (!values) return '—'
  // Try common field name patterns across templates
  const cognome = values['cognome_societa'] ?? values['Nominativo'] ?? values['cognome_nome'] ?? ''
  const nome    = values['nome'] ?? ''
  const full    = [cognome, nome].filter(Boolean).join(' ').trim()
  return full || '—'
}

// Extract a vehicle identifier
function vehicleId(values: Record<string, string> | null): string {
  if (!values) return ''
  return (
    values['targa_veicolo'] ??
    values['targa'] ??
    values['targa_numero'] ??
    values['modello_veicolo'] ??
    values['tipo'] ??
    ''
  )
}

function formatDate(iso: string): string {
  const d = new Date(iso)
  return d.toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' })
    + ' ' + d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })
}

// Short template badge label
function templateBadge(name: string): string {
  if (name.toLowerCase().includes('contratto')) return 'Contratto'
  if (name.toLowerCase().includes('permut'))    return 'Permuta'
  if (name.toLowerCase().includes('tt2120'))    return 'TT2120'
  return name.split(' ')[0]
}

function badgeColor(name: string): string {
  if (name.toLowerCase().includes('contratto')) return 'bg-blue-50 text-blue-700'
  if (name.toLowerCase().includes('permut'))    return 'bg-emerald-50 text-emerald-700'
  if (name.toLowerCase().includes('tt2120'))    return 'bg-violet-50 text-violet-700'
  return 'bg-gray-100 text-gray-600'
}
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h1 class="text-base font-semibold text-gray-900">Archivio compilazioni</h1>
        <span v-if="compiled.total" class="text-xs text-gray-400">{{ compiled.total }} {{ compiled.total === 1 ? 'documento' : 'documenti' }}</span>
      </div>
    </template>

    <div class="max-w-4xl space-y-4">

      <!-- Search -->
      <div class="relative max-w-xs">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z"/>
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Cerca cliente o targa..."
          class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
        />
      </div>

      <!-- List -->
      <div v-if="compiled.data.length" class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-50">
        <div
          v-for="item in compiled.data"
          :key="item.id"
          class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50/60 transition group"
        >
          <!-- Date -->
          <div class="w-36 shrink-0">
            <p class="text-xs font-medium text-gray-800">{{ formatDate(item.created_at).split(' ').slice(0,3).join(' ') }}</p>
            <p class="text-[11px] text-gray-400">{{ formatDate(item.created_at).split(' ')[3] }}</p>
          </div>

          <!-- Badge template -->
          <span class="shrink-0 px-2 py-0.5 rounded-md text-[11px] font-semibold" :class="badgeColor(item.template_name)">
            {{ templateBadge(item.template_name) }}
          </span>

          <!-- Client + vehicle -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">{{ clientName(item.values) }}</p>
            <p v-if="vehicleId(item.values)" class="text-[11px] text-gray-400 font-mono uppercase tracking-wide">{{ vehicleId(item.values) }}</p>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-3 opacity-0 group-hover:opacity-100 transition">
            <a
              :href="route('compiled.download', item.id)"
              target="_blank"
              rel="noopener"
              class="flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800 font-medium transition"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
              Scarica
            </a>
            <button
              @click="destroy(item.id)"
              class="text-xs text-gray-300 hover:text-red-500 transition"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div v-else class="bg-white rounded-xl border border-gray-200 px-8 py-20 text-center">
        <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
        </svg>
        <p class="text-sm text-gray-400 mb-4">{{ search ? 'Nessun risultato.' : 'Nessuna compilazione ancora.' }}</p>
        <Link
          v-if="!search"
          :href="route('compiled.create')"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition"
        >
          Crea la prima compilazione
        </Link>
      </div>

      <!-- Pagination -->
      <div v-if="compiled.total > 30" class="flex items-center justify-between text-sm text-gray-400">
        <span class="text-xs">{{ compiled.from }}–{{ compiled.to }} di {{ compiled.total }}</span>
        <div class="flex gap-1">
          <template v-for="link in compiled.links" :key="link.label">
            <Link
              v-if="link.url"
              :href="link.url"
              :class="['px-3 py-1 rounded border text-xs transition', link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-200 hover:bg-gray-50 text-gray-600']"
              v-html="link.label"
            />
            <span v-else class="px-3 py-1 rounded border border-gray-100 text-xs text-gray-300" v-html="link.label"/>
          </template>
        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>
