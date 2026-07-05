<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link, router } from '@inertiajs/vue3'

interface Template {
  id: number
  name: string
  fields_schema: object[] | null
  created_at: string
  updated_at: string
}

defineProps<{ templates: Template[] }>()

function destroy(id: number, name: string) {
  if (!confirm(`Eliminare il template "${name}"? L'operazione non è reversibile.`)) return
  router.delete(route('templates.destroy', id))
}
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h1 class="text-base font-semibold text-gray-900">Template</h1>
        <Link :href="route('templates.create')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nuovo
        </Link>
      </div>
    </template>

    <div class="max-w-4xl">
      <div v-if="templates.length" class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
        <div v-for="tpl in templates" :key="tpl.id" class="px-5 py-4 flex items-center justify-between gap-4">
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-900 truncate">{{ tpl.name }}</p>
            <p class="text-xs text-gray-400 mt-0.5">
              {{ tpl.fields_schema?.length ?? 0 }} campi
            </p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <Link
              :href="route('compiled.create', { template: tpl.id })"
              class="px-3 py-1.5 text-xs text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition"
            >
              Compila
            </Link>
            <Link
              :href="route('templates.edit', tpl.id)"
              class="px-3 py-1.5 text-xs text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition"
            >
              Modifica
            </Link>
            <button
              @click="destroy(tpl.id, tpl.name)"
              class="px-3 py-1.5 text-xs text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition"
            >
              Elimina
            </button>
          </div>
        </div>
      </div>

      <div v-else class="bg-white rounded-xl border border-gray-200 px-8 py-16 text-center">
        <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
        </svg>
        <p class="text-sm text-gray-500 mb-4">Nessun template ancora.</p>
        <Link :href="route('templates.create')" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
          Crea il primo template
        </Link>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
