<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
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
  <Head title="Accedi — Moduli" />

  <div class="min-h-screen bg-gray-50 flex items-center justify-center px-4">
    <div class="w-full max-w-sm">

      <!-- Logo / brand -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-600 mb-4">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Moduli</h1>
        <p class="text-sm text-gray-500 mt-1">Gestione e compilazione documenti</p>
      </div>

      <!-- Card -->
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
              placeholder="nome@esempio.it"
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
              <a
                v-if="canResetPassword"
                :href="route('password.request')"
                class="text-xs text-indigo-600 hover:underline"
              >
                Password dimenticata?
              </a>
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
    </div>
  </div>
</template>
