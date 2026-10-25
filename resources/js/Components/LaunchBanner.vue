<script setup lang="ts">
import { useOffer } from '@/offer'

// Barra dell'offerta di lancio con conto alla rovescia vero. Sparisce da sola alla scadenza.
const { attiva, offerta, countdown } = useOffer()
</script>

<template>
  <div v-if="attiva" class="bg-gray-900 text-white text-xs sm:text-sm">
    <div class="max-w-7xl mx-auto px-4 py-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-center">
      <span>
        <strong class="font-semibold">Prezzo di lancio {{ offerta.lancio }} €/mese</strong>
        <span class="text-gray-300"> · poi {{ offerta.prezzo_pieno }} €. Chi si abbona ora resta a {{ offerta.lancio }} €.</span>
      </span>
      <span class="inline-flex items-center gap-1.5 rounded-md bg-white/10 px-2 py-0.5 font-semibold tabular-nums">
        Scade tra {{ countdown }}
      </span>
    </div>
  </div>
</template>
