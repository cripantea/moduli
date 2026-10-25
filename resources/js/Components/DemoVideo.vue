<script setup lang="ts">
import { onMounted, ref } from 'vue'

// Video "come funziona" (20 s): verticale 4:5 da telefono, orizzontale 16:9 da computer.
// Si sceglie una sola versione all'apertura, così il telefono non scarica anche l'altra.
const vertical = ref(false)
onMounted(() => { vertical.value = window.matchMedia('(max-width: 767px)').matches })
</script>

<template>
  <video
    :key="vertical ? 'v' : 'h'"
    class="w-full rounded-2xl border border-gray-200 bg-[#f5f5f7]"
    :src="vertical ? '/videos/fusion-moduli-4x5.mp4' : '/videos/fusion-moduli.mp4'"
    :poster="vertical ? '/videos/fusion-moduli-4x5-poster.jpg' : '/videos/fusion-moduli-poster.jpg'"
    :width="vertical ? 1080 : 1920"
    :height="vertical ? 1350 : 1080"
    autoplay
    muted
    loop
    playsinline
    preload="metadata"
    aria-label="Video: carichi il PDF, l'AI trova i campi, lo compili in pochi secondi e lo ritrovi in archivio"
  />
</template>
