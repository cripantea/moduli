import { usePage } from '@inertiajs/vue3'
import { computed, onMounted, onUnmounted, ref } from 'vue'

// Offerta di lancio condivisa dal server: prezzo attuale e scadenza vera (uguale per tutti).
export interface Offerta { attiva: boolean; scade: string; prezzo: number; prezzo_pieno: number; lancio: number }

export function useOffer() {
  const offerta = computed(() => usePage().props.offerta as Offerta)
  const now = ref(Date.now())
  let timer: number | undefined
  onMounted(() => { timer = window.setInterval(() => { now.value = Date.now() }, 1000) })
  onUnmounted(() => window.clearInterval(timer))

  const left = computed(() => Math.max(0, new Date(offerta.value.scade).getTime() - now.value))
  const attiva = computed(() => offerta.value.attiva && left.value > 0)
  const prezzo = computed(() => (attiva.value ? offerta.value.lancio : offerta.value.prezzo_pieno))
  const scadenza = computed(() =>
    new Date(offerta.value.scade).toLocaleString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit', timeZone: 'Europe/Rome' }),
  )
  const parts = computed(() => {
    const s = Math.floor(left.value / 1000)
    return { g: Math.floor(s / 86400), h: Math.floor((s % 86400) / 3600), m: Math.floor((s % 3600) / 60), s: s % 60 }
  })
  const countdown = computed(() => {
    const p = parts.value
    const pad = (n: number) => String(n).padStart(2, '0')
    return `${p.g > 0 ? `${p.g}g ` : ''}${pad(p.h)}:${pad(p.m)}:${pad(p.s)}`
  })

  return { offerta, attiva, prezzo, scadenza, countdown, parts }
}
