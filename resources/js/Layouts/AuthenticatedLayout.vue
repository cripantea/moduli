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

const nav = [
  { name: 'Dashboard',   href: route('dashboard'),      icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: 'Template',    href: route('templates.index'), icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z' },
  { name: 'Compilazioni', href: route('compiled.index'), icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
]

function isActive(href: string): boolean {
  if (href === route('dashboard')) return currentPath.value === '/'
  return currentPath.value.startsWith(new URL(href, window.location.origin).pathname)
}

function logout() {
  router.post(route('logout'))
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">

    <!-- Sidebar -->
    <aside class="w-56 bg-white border-r border-gray-200 flex flex-col shrink-0">
      <div class="h-16 flex items-center px-5 border-b border-gray-100">
        <span class="text-lg font-bold text-gray-900 tracking-tight">Moduli</span>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1">
        <Link
          v-for="item in nav"
          :key="item.name"
          :href="item.href"
          :class="[
            'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition',
            isActive(item.href)
              ? 'bg-indigo-50 text-indigo-700'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="item.icon"/>
          </svg>
          {{ item.name }}
        </Link>
      </nav>

      <div class="px-3 py-4 border-t border-gray-100 space-y-1">
        <Link
          :href="route('profile.edit')"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-50 transition"
        >
          <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold shrink-0">
            {{ user.name.split(' ').map((w: string) => w[0]).join('').slice(0, 2).toUpperCase() }}
          </div>
          <span class="truncate">{{ user.name }}</span>
        </Link>
        <button
          @click="logout"
          class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-red-50 hover:text-red-600 transition"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          Esci
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Header slot -->
      <header v-if="$slots.header" class="h-16 bg-white border-b border-gray-200 flex items-center px-6">
        <slot name="header"/>
      </header>

      <!-- Flash -->
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
