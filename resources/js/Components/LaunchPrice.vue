<script setup lang="ts">
import { useOffer } from '@/offer'

// Riquadro prezzo con offerta di lancio: prezzo, prezzo futuro, scadenza e conto alla rovescia.
const { attiva, offerta, prezzo, scadenza, countdown } = useOffer()
</script>

<template>
  <div>
    <p>
      <span class="text-5xl font-extrabold text-gray-900">{{ prezzo }} €</span>
      <span class="text-gray-500"> / mese, IVA inclusa</span>
    </p>
    <div v-if="attiva" class="mt-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
      <p><strong>Prezzo di lancio.</strong> Dopo {{ scadenza }} il prezzo diventa {{ offerta.prezzo_pieno }} €/mese. Chi si abbona prima resta a {{ offerta.lancio }} € finché resta abbonato.</p>
      <p class="mt-1.5 font-semibold tabular-nums">Scade tra {{ countdown }}</p>
    </div>
  </div>
</template>
