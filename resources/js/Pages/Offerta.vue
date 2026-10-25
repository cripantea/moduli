<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { track } from '@/tracking'
import { useOffer } from '@/offer'

// Ultimo passo della registrazione: l'offerta (prezzo di lancio + giorni gratis) e il tecnico subito dopo.
// Niente menu dell'app: una decisione sola. Si può saltare e provare da soli.
const props = defineProps<{
  nome: string
  settore: string | null
  volume: string | null
  giorniGratis: number
}>()

const { attiva, offerta, prezzo, countdown } = useOffer()
const loading = ref(false)
const flashError = computed(() => (usePage().props.flash as { error?: string | null }).error)

// Tempo risparmiato: 2:52 con Adobe contro 30 secondi = circa 2 minuti e 20 a modulo.
const oreAlMese = computed(() => {
  const aSettimana = { 'Meno di 10': 6, 'Da 10 a 50': 30, 'Più di 50': 60 }[props.volume ?? ''] ?? 0
  return Math.round((aSettimana * 142 * 4.3) / 3600)
})

function attiva7() {
  loading.value = true
  track({ event: 'InitiateCheckout', params: { value: prezzo.value, currency: 'EUR' } })
  router.post(route('abbonamento.checkout', { benvenuto: 1 }), {}, { onFinish: () => { loading.value = false } })
}
</script>

<template>
  <Head title="La tua offerta — Fusion Moduli" />

  <div class="min-h-screen bg-gray-50">
    <div class="flex items-center justify-between px-4 sm:px-6 h-14 border-b border-gray-100 bg-white">
      <span class="flex items-center gap-2">
        <span class="w-7 h-7 rounded-lg bg-blue-700 flex items-center justify-center">
          <svg class="w-4 h-4" viewBox="0 0 18 18" fill="none"><g transform="translate(2.2, 2.7) scale(0.089)"><path fill="white" d="M 82 7 C 97 0 114 17 111 43 C 108 68 88 80 60 82 C 73 65 75 48 62 36 C 50 24 30 22 25 11 C 18 -2 32 -8 48 0 C 60 6 74 7 82 7 Z"/><path fill="white" d="M 45 84 C 61 75 78 90 74 114 C 70 136 50 150 27 150 C 38 134 40 117 28 105 C 17 93 1 93 -2 79 C -6 63 8 52 23 57 C 34 61 43 77 45 84 Z"/></g></svg>
        </span>
        <span class="text-sm font-bold text-gray-900">Fusion Moduli</span>
      </span>
      <span class="text-xs text-gray-500">Ultimo passo</span>
    </div>

    <div class="max-w-md mx-auto px-4 py-6 sm:py-10">
      <div class="h-1.5 rounded-full bg-indigo-600" />
      <p class="mt-5 inline-flex items-center gap-1.5 rounded-full bg-amber-100 text-amber-900 px-3 py-1 text-xs font-bold">🎁 Offerta speciale per te</p>
      <h1 class="mt-3 text-[1.75rem] leading-tight font-extrabold text-gray-900">
        {{ nome }}, <template v-if="giorniGratis">{{ giorniGratis }} giorni gratis e </template>{{ prezzo }} € invece di {{ offerta.prezzo_pieno }} €
      </h1>
      <p v-if="oreAlMese > 0" class="mt-2 text-gray-600">
        Con {{ volume?.toLowerCase() }} moduli a settimana risparmi circa <strong class="text-gray-900">{{ oreAlMese }} {{ oreAlMese === 1 ? 'ora' : 'ore' }} al mese</strong> rispetto ad Adobe.
      </p>

      <div class="mt-5 bg-white rounded-2xl border-2 border-indigo-600 p-5">
        <div class="flex items-end justify-between gap-3">
          <div>
            <p class="text-sm text-gray-500 line-through">{{ offerta.prezzo_pieno }} €/mese</p>
            <p class="text-3xl font-extrabold text-gray-900">{{ prezzo }} €<span class="text-base font-semibold text-gray-500">/mese</span></p>
            <p v-if="attiva" class="text-xs font-semibold text-indigo-700">Bloccato finché resti abbonato</p>
          </div>
          <div v-if="giorniGratis" class="text-right">
            <p class="text-xs font-medium text-gray-500">Oggi paghi</p>
            <p class="text-3xl font-extrabold text-green-600">0 €</p>
          </div>
        </div>

        <ol class="mt-5 space-y-3 border-t border-gray-100 pt-4 text-sm">
          <li class="flex gap-3"><span class="w-14 shrink-0 font-bold text-gray-900">Oggi</span><span class="text-gray-700">Attivi e prenoti la videochiamata con il tecnico</span></li>
          <li v-if="giorniGratis" class="flex gap-3"><span class="w-14 shrink-0 font-bold text-gray-900">{{ giorniGratis }} giorni</span><span class="text-gray-700">Gratis: il tecnico imposta con te i tuoi moduli e li usi subito</span></li>
          <li class="flex gap-3"><span class="w-14 shrink-0 font-bold text-gray-900">Poi</span><span class="text-gray-700">{{ prezzo }} €/mese. Disdici con un clic quando vuoi<template v-if="giorniGratis">: se lo fai prima, non paghi nulla</template></span></li>
        </ol>

        <button
          type="button"
          :disabled="loading"
          class="mt-5 w-full py-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-lg font-bold transition"
          @click="attiva7"
        >
          {{ loading ? 'Apro il pagamento…' : giorniGratis ? `Attiva i ${giorniGratis} giorni gratis` : 'Attiva e prenota il tecnico' }}
        </button>
        <p class="mt-2 text-center text-xs text-gray-500">Carta, Apple Pay o Google Pay · pagamento sicuro con Stripe</p>
        <p v-if="flashError" class="mt-2 text-center text-sm text-red-600">{{ flashError }}</p>
      </div>

      <p v-if="attiva" class="mt-4 text-center text-sm text-gray-700">
        Il prezzo di lancio scade tra <strong class="tabular-nums">{{ countdown }}</strong>
      </p>
      <p class="mt-3 text-center text-xs text-gray-500">
        Dopo il primo pagamento, se non fa per te ti rimborsiamo entro 30 giorni, senza domande.
      </p>

      <Link :href="route('onboarding')" class="mt-6 block text-center text-sm text-gray-400 hover:text-gray-700">
        No grazie, prima provo da solo
      </Link>
    </div>
  </div>
</template>
