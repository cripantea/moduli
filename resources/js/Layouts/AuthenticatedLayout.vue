<script setup lang="ts">
import { computed, ref } from 'vue'
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

const initials = computed(() =>
  user.value.name
    .split(' ')
    .map((w: string) => w[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
)
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">

    <!-- ── Sidebar ──────────────────────────────────────── -->
    <aside class="w-56 bg-white border-r border-gray-100 flex flex-col shrink-0">

      <!-- Logo -->
      <div class="h-14 flex items-center px-4 border-b border-gray-100 gap-2">
        <div class="w-7 h-7 rounded-lg bg-blue-700 flex items-center justify-center shrink-0">
          <svg class="w-4 h-4" viewBox="0 0 18 18" fill="none">
            <g transform="translate(2.2, 2.7) scale(0.089)">
              <path fill="white" d="M 82 7 C 97 0 114 17 111 43 C 108 68 88 80 60 82 C 73 65 75 48 62 36 C 50 24 30 22 25 11 C 18 -2 32 -8 48 0 C 60 6 74 7 82 7 Z"/>
              <path fill="white" d="M 45 84 C 61 75 78 90 74 114 C 70 136 50 150 27 150 C 38 134 40 117 28 105 C 17 93 1 93 -2 79 C -6 63 8 52 23 57 C 34 61 43 77 45 84 Z"/>
            </g>
          </svg>
        </div>
        <span class="text-sm font-bold text-gray-900 tracking-tight">Fusion Moduli</span>
      </div>

      <!-- Nav -->
      <div class="flex-1 flex flex-col px-3 py-4 gap-1 overflow-y-auto">

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

        <!-- Template — same prominence as Archivio -->
        <Link
          :href="route('templates.index')"
          :class="[
            'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition',
            isActive(route('templates.index'))
              ? 'bg-indigo-50 text-indigo-700'
              : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
          ]"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
          </svg>
          Template
        </Link>

        <!-- Superadmin link (only for superadmins) -->
        <div v-if="user.role === 'superadmin'" class="mt-3 pt-3 border-t border-gray-100">
          <Link
            :href="route('superadmin.dashboard')"
            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition"
          >
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Superadmin
          </Link>
        </div>

      </div>

      <!-- ── Bottom: user + logout ──────────────────────── -->
      <div class="px-3 py-3 border-t border-gray-100">
        <!-- User row -->
        <Link
          :href="route('profile.edit')"
          class="flex items-center gap-2.5 px-3 py-2 rounded-lg hover:bg-gray-50 transition group mb-0.5"
        >
          <div class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[11px] font-bold shrink-0">
            {{ initials }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-xs font-medium text-gray-800 truncate">{{ user.name }}</p>
            <p class="text-[10px] text-gray-400 truncate">{{ user.email }}</p>
          </div>
        </Link>

        <!-- Logout — explicit, always visible -->
        <button
          @click="logout"
          class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-500 hover:bg-red-50 hover:text-red-600 transition"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          Esci
        </button>
      </div>

    </aside>

    <!-- ── Main ──────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0">
      <header v-if="$slots.header" class="h-14 bg-white border-b border-gray-100 flex items-center px-6">
        <slot name="header"/>
      </header>

      <Transition
        enter-active-class="transition"
        enter-from-class="opacity-0 -translate-y-1"
        leave-active-class="transition"
        leave-to-class="opacity-0"
      >
        <div
          v-if="flash?.success || flash?.error"
          :class="[
            'mx-6 mt-4 px-4 py-3 rounded-lg text-sm font-medium',
            flash?.success
              ? 'bg-green-50 text-green-800 border border-green-200'
              : 'bg-red-50 text-red-800 border border-red-200'
          ]"
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
