<script setup lang="ts">
import { useOffer } from '@/offer'

// Barra dell'offerta di lancio con conto alla rovescia vero. Sparisce da sola alla scadenza.
// Da telefono una riga sola e corta; da computer il testo completo.
const { attiva, offerta, countdown } = useOffer()
</script>

<template>
  <div v-if="attiva" class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-3 py-1.5 sm:py-2 flex items-center justify-center gap-2 sm:gap-3 text-[12px] sm:text-sm whitespace-nowrap">
      <span class="sm:hidden"><strong class="font-semibold">Lancio {{ offerta.lancio }} €/mese</strong> <span class="text-gray-300">poi {{ offerta.prezzo_pieno }} €</span></span>
      <span class="hidden sm:inline">
        <strong class="font-semibold">Prezzo di lancio {{ offerta.lancio }} €/mese</strong>
        <span class="text-gray-300"> · poi {{ offerta.prezzo_pieno }} €. Chi si abbona ora resta a {{ offerta.lancio }} €.</span>
      </span>
      <span class="rounded-md bg-white/10 px-1.5 py-0.5 font-semibold tabular-nums">
        <span class="hidden sm:inline">Scade tra </span>{{ countdown }}
      </span>
    </div>
  </div>
</template>
