<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'

defineProps<{
  pronto: { id: number; template_name: string; created_at: string } | null
  prezzo: number
}>()
</script>

<template>
  <Head title="Attiva Moduli" />

  <AuthenticatedLayout>
    <template #header>
      <h1 class="text-base font-semibold text-gray-900">Attiva Moduli</h1>
    </template>

    <div class="max-w-xl mx-auto py-10 px-4">
      <div v-if="pronto" class="flex items-start gap-3 bg-green-50 border border-green-200 rounded-2xl px-5 py-4">
        <svg class="w-6 h-6 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <div>
          <p class="text-sm font-semibold text-green-900">"{{ pronto.template_name }}" è compilato e pronto</p>
          <p class="text-sm text-green-800">Attiva l'abbonamento per scaricarlo e compilare tutti i moduli che vuoi.</p>
        </div>
      </div>

      <div class="mt-6 bg-white border border-gray-200 rounded-2xl p-6">
        <p class="text-sm font-medium text-gray-500">Moduli</p>
        <p class="mt-1">
          <span class="text-4xl font-bold text-gray-900">{{ prezzo }} €</span>
          <span class="text-sm text-gray-500"> / mese</span>
        </p>

        <ul class="mt-5 space-y-2 text-sm text-gray-700">
          <li>✓ Moduli e compilazioni illimitati</li>
          <li>✓ L'AI riconosce i campi di ogni nuovo PDF</li>
          <li>✓ Archivio con ricerca e download</li>
          <li>✓ Videochiamata di onboarding con noi</li>
          <li>✓ Nessun vincolo: disdici quando vuoi</li>
        </ul>

        <!-- Il pagamento (Stripe) e la prenotazione della call arrivano nel passo 2. -->
        <button
          type="button"
          disabled
          class="mt-6 w-full px-6 py-3 rounded-xl bg-indigo-600 text-white text-sm font-semibold opacity-50 cursor-not-allowed"
        >
          Attiva e scarica
        </button>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
