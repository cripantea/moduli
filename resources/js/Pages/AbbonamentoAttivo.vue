<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps<{
  attivo: boolean
  pronto: { id: number; template_name: string } | null
  bookingUrl: string | null
}>()
</script>

<template>
  <Head title="Abbonamento attivo" />

  <AuthenticatedLayout>
    <template #header>
      <h1 class="text-base font-semibold text-gray-900">Benvenuto in Moduli</h1>
    </template>

    <div class="max-w-xl mx-auto py-10 px-4">
      <div v-if="attivo">
        <div class="flex items-start gap-3 bg-green-50 border border-green-200 rounded-2xl px-5 py-4">
          <svg class="w-6 h-6 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          <div>
            <p class="text-sm font-semibold text-green-900">Abbonamento attivo</p>
            <p class="text-sm text-green-800">Ora puoi scaricare i tuoi PDF e compilare tutti i moduli che vuoi.</p>
          </div>
        </div>

        <a
          v-if="pronto"
          :href="route('compiled.download', pronto.id)"
          target="_blank"
          rel="noopener"
          class="mt-4 flex items-center justify-center w-full px-6 py-3 rounded-xl border border-gray-300 bg-white text-sm font-semibold text-gray-900 hover:bg-gray-50 transition"
        >
          Scarica "{{ pronto.template_name }}"
        </a>

        <div v-if="bookingUrl" class="mt-6 bg-white border border-gray-200 rounded-2xl p-6">
          <h2 class="text-lg font-bold text-gray-900">Prenota la tua call di onboarding</h2>
          <p class="mt-2 text-sm text-gray-600">
            30 minuti in videochiamata: carichiamo insieme i tuoi moduli e li impostiamo perché siano pronti per il lavoro di ogni giorno.
          </p>
          <a
            :href="bookingUrl"
            target="_blank"
            rel="noopener"
            class="mt-5 flex items-center justify-center w-full px-6 py-3 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition"
          >
            Scegli giorno e ora →
          </a>
        </div>

        <p class="mt-6 text-center text-xs text-gray-500">
          Hai 30 giorni per decidere: se non fa per te ti rimborsiamo tutto.
          L'abbonamento lo gestisci (o disdici) quando vuoi da "Abbonamento" nel menu.
        </p>

        <Link :href="route('compiled.index')" class="mt-4 block text-center text-sm text-gray-500 hover:text-gray-800">
          Vai ai tuoi moduli
        </Link>
      </div>

      <div v-else class="text-center py-10">
        <h2 class="text-xl font-bold text-gray-900">Stiamo confermando il pagamento…</h2>
        <p class="mt-2 text-sm text-gray-600">Ci vuole qualche secondo. Ricarica la pagina tra poco.</p>
        <Link :href="route('abbonamento.attivo', pronto ? { pronto: pronto.id } : {})" class="mt-5 inline-block text-sm font-semibold text-indigo-600">
          Ricarica
        </Link>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
