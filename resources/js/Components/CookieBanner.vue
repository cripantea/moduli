<script setup lang="ts">
import { consent, trackingEnabled, setConsent, pageView } from '@/tracking'
import { ref } from 'vue'

const visible = ref(trackingEnabled && consent() === null)

function choose(value: 'si' | 'no') {
  setConsent(value)
  if (value === 'si') pageView()
  visible.value = false
}
</script>

<template>
  <div
    v-if="visible"
    class="fixed inset-x-0 bottom-0 z-50 p-2 sm:p-4 sm:bottom-4 sm:left-4 sm:right-auto sm:max-w-sm"
  >
    <div class="bg-white border border-gray-200 rounded-xl sm:rounded-2xl shadow-xl px-3 py-2.5 sm:p-5 flex items-center gap-3 sm:block">
      <p class="text-xs sm:text-sm text-gray-700 leading-snug">
        Cookie di Meta e Microsoft Clarity per capire quali pubblicità funzionano e migliorare il sito.
        <a href="https://fusionsoft.it/privacy" target="_blank" rel="noopener" class="text-indigo-600 underline">Privacy</a>
      </p>
      <div class="shrink-0 flex gap-2 sm:mt-4 sm:grid sm:grid-cols-2">
        <button type="button" class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg border border-gray-300 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="choose('no')">
          Rifiuta
        </button>
        <button type="button" class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-lg border border-gray-300 text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="choose('si')">
          Accetta
        </button>
      </div>
    </div>
  </div>
</template>
