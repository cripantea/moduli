<script setup lang="ts">
defineProps<{ dark?: boolean }>()
</script>

<template>
  <a
    href="https://fusionsoft.it"
    target="_blank"
    rel="noopener"
    :class="['inline-flex items-center gap-2 text-xs transition-colors', dark ? 'text-gray-400 hover:text-white' : 'text-gray-500 hover:text-gray-900']"
  >
    <img src="/images/fusionsoft-mark.png" alt="" class="h-5 w-5 object-contain" />
    <span>Un prodotto <strong class="font-semibold" :class="dark ? 'text-gray-200' : 'text-gray-700'">Fusionsoft</strong></span>
  </a>
</template>
