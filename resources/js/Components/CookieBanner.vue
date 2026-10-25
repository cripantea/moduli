<script setup lang="ts">
import { consent, trackingEnabled, setConsent, pageView } from '@/tracking'
import { onMounted, onUnmounted, ref } from 'vue'

const visible = ref(trackingEnabled && consent() === null)

const hide = () => { visible.value = false }
onMounted(() => window.addEventListener('consenso', hide))
onUnmounted(() => window.removeEventListener('consenso', hide))

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
    <div class="bg-white border border-gray-200 rounded-xl sm:rounded-2xl shadow-xl px-3 py-3 sm:p-5">
      <p class="text-xs sm:text-sm text-gray-700 leading-snug">
        Ci aiuti a migliorare? Usiamo cookie di Meta e Microsoft Clarity solo per capire quali annunci funzionano e come migliorare il sito.
        <a href="https://fusionsoft.it/privacy" target="_blank" rel="noopener" class="text-indigo-600 underline">Privacy</a>
      </p>
      <div class="mt-2.5 sm:mt-4 grid grid-cols-2 gap-2">
        <button type="button" class="py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50" @click="choose('no')">
          Rifiuta
        </button>
        <button type="button" class="py-2 rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700" @click="choose('si')">
          Accetta
        </button>
      </div>
    </div>
  </div>
</template>
