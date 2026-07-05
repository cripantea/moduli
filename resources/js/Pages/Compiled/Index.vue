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

function destroy(id: number, name: string) {
  if (!confirm(`Eliminare la compilazione "${name}"?`)) return
  router.delete(route('compiled.destroy', id))
}
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h1 class="text-base font-semibold text-gray-900">Compilazioni</h1>
        <Link :href="route('compiled.create')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nuova
        </Link>
      </div>
    </template>

    <div class="max-w-5xl space-y-4">
      <!-- Search -->
      <div class="relative max-w-sm">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z"/>
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Cerca template o valori..."
          class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
        />
      </div>

      <!-- Table -->
      <div v-if="compiled.data.length" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">File</th>
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Template</th>
              <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Data</th>
              <th class="px-5 py-3 w-24"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="item in compiled.data" :key="item.id" class="hover:bg-gray-50/50">
              <td class="px-5 py-3 font-medium text-gray-900 truncate max-w-xs">{{ item.original_filename }}</td>
              <td class="px-5 py-3 text-gray-500 truncate max-w-xs">{{ item.template_name }}</td>
              <td class="px-5 py-3 text-gray-400 whitespace-nowrap">{{ item.created_at }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-2 justify-end">
                  <a :href="route('compiled.download', item.id)" class="text-xs text-indigo-600 hover:underline">Scarica</a>
                  <button @click="destroy(item.id, item.original_filename)" class="text-xs text-red-500 hover:text-red-700">Elimina</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="bg-white rounded-xl border border-gray-200 px-8 py-16 text-center">
        <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm text-gray-500 mb-4">{{ search ? 'Nessun risultato trovato.' : 'Nessuna compilazione ancora.' }}</p>
        <Link v-if="!search" :href="route('compiled.create')" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
          Crea la prima compilazione
        </Link>
      </div>

      <!-- Pagination -->
      <div v-if="compiled.total > 30" class="flex items-center justify-between text-sm text-gray-500">
        <span>{{ compiled.from }}–{{ compiled.to }} di {{ compiled.total }}</span>
        <div class="flex gap-1">
          <template v-for="link in compiled.links" :key="link.label">
            <Link
              v-if="link.url"
              :href="link.url"
              :class="['px-3 py-1 rounded border text-xs transition', link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-300 hover:bg-gray-50']"
              v-html="link.label"
            />
            <span v-else class="px-3 py-1 rounded border border-gray-200 text-xs text-gray-300" v-html="link.label"/>
          </template>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
