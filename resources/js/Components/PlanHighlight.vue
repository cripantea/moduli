<script setup lang="ts">
import { planHighlight } from '@/plan'
</script>

<template>
  <div class="rounded-xl bg-indigo-600 px-4 py-3 text-white">
    <p class="text-base font-bold">{{ planHighlight }}</p>
    <p class="text-sm text-indigo-100">Carichi tutti i moduli che usi e li compili quante volte vuoi.</p>
  </div>
</template>
