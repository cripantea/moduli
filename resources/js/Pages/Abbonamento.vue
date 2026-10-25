<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { track } from '@/tracking'
import LaunchPrice from '@/Components/LaunchPrice.vue'
import PlanHighlight from '@/Components/PlanHighlight.vue'
import { fairUse, vatNote } from '@/plan'

const props = defineProps<{
  pronto: { id: number; template_name: string; created_at: string } | null
  prezzo: number
}>()

const loading = ref(false)
const flashError = computed(() => (usePage().props.flash as { error?: string | null }).error)

function checkout() {
  loading.value = true
  track({ event: 'InitiateCheckout', params: { value: props.prezzo, currency: 'EUR' } })
  router.post(route('abbonamento.checkout', props.pronto ? { pronto: props.pronto.id } : {}), {}, {
    onFinish: () => { loading.value = false },
  })
}
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
        <LaunchPrice class="mt-1" />

        <PlanHighlight class="mt-5" />
        <ul class="mt-5 space-y-2 text-sm text-gray-700">
          <li>✓ L'AI riconosce i campi di ogni nuovo PDF</li>
          <li>✓ Archivio con ricerca e download</li>
          <li>✓ 30 minuti di videochiamata con un tecnico che imposta tutto con te</li>
          <li>✓ Nessun vincolo: disdici quando vuoi, con un clic</li>
        </ul>

        <div class="mt-5 flex items-start gap-3 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">
          <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
          </svg>
          <p class="text-sm text-amber-900">
            <span class="font-semibold">Soddisfatto o rimborsato.</span>
            Se entro 30 giorni Moduli non fa per te, ti restituiamo i soldi. Senza domande.
          </p>
        </div>

        <button
          type="button"
          :disabled="loading"
          class="mt-6 w-full px-6 py-3 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50 transition"
          @click="checkout"
        >
          {{ loading ? 'Apro il pagamento…' : 'Attiva e scarica' }}
        </button>
        <p class="mt-3 text-center text-xs text-gray-500">Pagamento sicuro con Stripe · carta o Apple/Google Pay</p>
        <p class="mt-2 text-center text-xs text-gray-400">{{ vatNote }} {{ fairUse }}</p>
        <p v-if="flashError" class="mt-3 text-center text-sm text-red-600">{{ flashError }}</p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
