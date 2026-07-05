<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps<{
  templatesCount: number
  compiledCount: number
  recentCompiled: { id: number; template_name: string; original_filename: string; created_at: string }[]
}>()
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <h1 class="text-base font-semibold text-gray-900">Dashboard</h1>
    </template>

    <div class="max-w-4xl space-y-6">
      <!-- Stats -->
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
          <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900">{{ templatesCount }}</p>
            <p class="text-sm text-gray-500">Template</p>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
          <div class="w-10 h-10 rounded-lg bg-green-50 text-green-600 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900">{{ compiledCount }}</p>
            <p class="text-sm text-gray-500">Compilazioni totali</p>
          </div>
        </div>
      </div>

      <!-- Quick actions -->
      <div class="flex gap-3">
        <Link :href="route('compiled.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nuova compilazione
        </Link>
        <Link :href="route('templates.create')" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nuovo template
        </Link>
      </div>

      <!-- Recent -->
      <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-gray-900">Compilazioni recenti</h2>
          <Link :href="route('compiled.index')" class="text-xs text-indigo-600 hover:underline">Vedi tutto</Link>
        </div>
        <div v-if="recentCompiled.length" class="divide-y divide-gray-50">
          <div v-for="item in recentCompiled" :key="item.id" class="px-5 py-3 flex items-center justify-between">
            <div class="min-w-0">
              <p class="text-sm text-gray-900 truncate">{{ item.original_filename }}</p>
              <p class="text-xs text-gray-400">{{ item.template_name }}</p>
            </div>
            <a :href="route('compiled.download', item.id)" class="ml-4 text-xs text-indigo-600 hover:underline shrink-0">Scarica</a>
          </div>
        </div>
        <div v-else class="px-5 py-10 text-center text-sm text-gray-400">
          Nessuna compilazione ancora.
          <Link :href="route('compiled.create')" class="text-indigo-600 hover:underline">Creane una</Link>.
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
