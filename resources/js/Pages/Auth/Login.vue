<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import InputError from '@/Components/InputError.vue'

defineProps<{
  canResetPassword?: boolean
  status?: string
}>()

const form = useForm({
  email:    '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head title="Accedi — Fusion Moduli" />

  <div class="min-h-screen bg-gray-50 flex flex-col">

    <!-- Top nav -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-white">
      <Link href="/" class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-lg bg-blue-700 flex items-center justify-center">
          <svg class="w-4 h-4" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g transform="translate(2.2, 2.7) scale(0.089)">
              <path fill="white" d="M 82 7 C 97 0 114 17 111 43 C 108 68 88 80 60 82 C 73 65 75 48 62 36 C 50 24 30 22 25 11 C 18 -2 32 -8 48 0 C 60 6 74 7 82 7 Z"/>
              <path fill="white" d="M 45 84 C 61 75 78 90 74 114 C 70 136 50 150 27 150 C 38 134 40 117 28 105 C 17 93 1 93 -2 79 C -6 63 8 52 23 57 C 34 61 43 77 45 84 Z"/>
            </g>
          </svg>
        </div>
        <span class="text-sm font-bold text-gray-900">Fusion Moduli</span>
      </Link>
      <Link href="/register" class="text-sm text-indigo-600 font-semibold hover:underline">
        Inizia gratis →
      </Link>
    </div>

    <!-- Login form -->
    <div class="flex-1 flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-sm">

        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-gray-900">Accedi al tuo account</h1>
          <p class="text-sm text-gray-500 mt-1.5">Bentornato su Fusion Moduli</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">

          <div v-if="status" class="mb-5 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
            {{ status }}
          </div>

          <form @submit.prevent="submit" class="space-y-5">

            <div>
              <label for="email" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">
                Email
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="username"
                required
                autofocus
                placeholder="nome@azienda.it"
                class="w-full border rounded-lg px-3 py-2.5 text-sm outline-none focus:ring-2 transition"
                :class="form.errors.email
                  ? 'border-red-400 focus:ring-red-300'
                  : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'"
              />
              <InputError :message="form.errors.email" class="mt-1.5" />
            </div>

            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide">
                  Password
                </label>
                <Link
                  v-if="canResetPassword"
                  :href="route('password.request')"
                  class="text-xs text-indigo-600 hover:underline"
                >
                  Password dimenticata?
                </Link>
              </div>
              <input
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                required
                class="w-full border rounded-lg px-3 py-2.5 text-sm outline-none focus:ring-2 transition"
                :class="form.errors.password
                  ? 'border-red-400 focus:ring-red-300'
                  : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'"
              />
              <InputError :message="form.errors.password" class="mt-1.5" />
            </div>

            <div class="flex items-center gap-2">
              <input v-model="form.remember" type="checkbox" id="remember"
                     class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"/>
              <label for="remember" class="text-sm text-gray-500 cursor-pointer select-none">Ricordami</label>
            </div>

            <button
              type="submit"
              :disabled="form.processing"
              class="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed rounded-lg text-white text-sm font-semibold transition"
            >
              <svg v-if="form.processing" class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              {{ form.processing ? 'Accesso in corso…' : 'Accedi' }}
            </button>

          </form>
        </div>

        <p class="text-center text-sm text-gray-500 mt-6">
          Non hai un account?
          <Link href="/register" class="text-indigo-600 font-semibold hover:underline">Inizia gratis</Link>
        </p>

      </div>
    </div>
  </div>
</template>
