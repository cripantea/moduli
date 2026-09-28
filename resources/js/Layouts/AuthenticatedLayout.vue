<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'
import type { PageProps } from '@/types'

const page  = usePage<PageProps>()
const user  = computed(() => page.props.auth.user)
const flash = computed(() => page.props.flash as { success?: string; error?: string } | null)

const currentPath = computed(() => {
  const url = (page as any).url ?? ''
  return typeof url === 'string' ? url : ''
})

function isActive(href: string): boolean {
  const path = new URL(href, window.location.origin).pathname
  return currentPath.value.startsWith(path)
}

function logout() {
  router.post(route('logout'))
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">

    <!-- Sidebar -->
    <aside class="w-52 bg-white border-r border-gray-100 flex flex-col shrink-0">

      <!-- Logo -->
      <div class="h-14 flex items-center px-5 border-b border-gray-100">
        <span class="text-base font-bold text-gray-900 tracking-tight">Moduli</span>
        <span class="ml-1.5 text-[10px] font-medium text-gray-400 uppercase tracking-wide">FusionSoft</span>
      </div>

      <div class="flex-1 flex flex-col px-3 py-4 gap-1">

        <!-- Primary CTA -->
        <Link
          :href="route('compiled.create')"
          class="flex items-center justify-center gap-2 px-3 py-2.5 mb-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
          </svg>
          Nuova compilazione
        </Link>

        <!-- Archivio -->
        <Link
          :href="route('compiled.index')"
          :class="[
            'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition',
            isActive(route('compiled.index')) && !currentPath.includes('/create')
              ? 'bg-indigo-50 text-indigo-700'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
          </svg>
          Archivio
        </Link>

        <!-- Spacer -->
        <div class="flex-1"/>

        <!-- Templates — secondary -->
        <div class="border-t border-gray-100 pt-3 mt-1">
          <p class="px-3 mb-1 text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Configurazione</p>
          <Link
            :href="route('templates.index')"
            :class="[
              'flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition',
              isActive(route('templates.index'))
                ? 'bg-gray-100 text-gray-800 font-medium'
                : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700'
            ]"
          >
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
            </svg>
            Template
          </Link>
        </div>

      </div>

      <!-- User + logout -->
      <div class="px-3 py-3 border-t border-gray-100 space-y-0.5">
        <Link
          :href="route('profile.edit')"
          class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition"
        >
          <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-bold shrink-0">
            {{ user.name.split(' ').map((w: string) => w[0]).join('').slice(0, 2).toUpperCase() }}
          </div>
          <span class="truncate text-xs">{{ user.name }}</span>
        </Link>
        <button
          @click="logout"
          class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-gray-400 hover:bg-red-50 hover:text-red-500 transition"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          <span class="text-xs">Esci</span>
        </button>
      </div>

    </aside>

    <!-- Main -->
    <div class="flex-1 flex flex-col min-w-0">
      <header v-if="$slots.header" class="h-14 bg-white border-b border-gray-100 flex items-center px-6">
        <slot name="header"/>
      </header>

      <Transition enter-active-class="transition" enter-from-class="opacity-0 -translate-y-1" leave-active-class="transition" leave-to-class="opacity-0">
        <div
          v-if="flash?.success || flash?.error"
          :class="['mx-6 mt-4 px-4 py-3 rounded-lg text-sm font-medium', flash?.success ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200']"
        >
          {{ flash?.success ?? flash?.error }}
        </div>
      </Transition>

      <main class="flex-1 p-6">
        <slot/>
      </main>
    </div>

  </div>
</template>
