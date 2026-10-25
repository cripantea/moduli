<script setup lang="ts">
import DemoVideo from '@/Components/DemoVideo.vue'
import { whatsappUrl } from '@/support'
import { Head, Link } from '@inertiajs/vue3'
import LaunchBanner from '@/Components/LaunchBanner.vue'
import LaunchPrice from '@/Components/LaunchPrice.vue'
import PlanHighlight from '@/Components/PlanHighlight.vue'
import { fairUse, vatNote } from '@/plan'
import { useOffer } from '@/offer'

const { attiva: offertaAttiva, prezzo, offerta } = useOffer()

// Landing dedicata alle campagne Meta: una promessa, un pulsante, niente menu.
const steps = [
  { title: 'Carichi il tuo PDF', desc: 'Il modulo che compili tutti i giorni, così com’è. L’AI trova da sola i campi da compilare.' },
  { title: 'Un tecnico lo imposta con te', desc: '30 minuti in videochiamata: sistemiamo insieme i campi e i moduli che usi di più.' },
  { title: 'Da lì, 30 secondi a modulo', desc: 'Scrivi i dati, il PDF si riempie, lo scarichi. Ogni compilazione resta in archivio.' },
]

const included = [
  '30 minuti di videochiamata con un tecnico',
  'L’AI che legge ogni nuovo PDF',
  'Archivio di tutte le compilazioni',
  'Assistenza in chat e su WhatsApp',
]

const faqs = [
  { q: 'Il tecnico è compreso nel prezzo?', a: 'Sì. Dopo l’attivazione prenoti tu giorno e ora: 30 minuti in videochiamata per impostare i tuoi moduli. Nessun costo in più.' },
  { q: 'Funziona con i miei moduli?', a: 'Sì: carichi il PDF che usi già. Deleghe, denunce, richieste, contratti, schede cliente.' },
  { q: 'Ricevo la fattura? C’è l’IVA?', a: 'Sì, fattura elettronica: i dati li inserisci al pagamento. Fusionsoft è in regime forfettario, quindi niente IVA: il prezzo che vedi è quello che paghi.' },
  { q: 'E se non fa per me?', a: 'Entro 30 giorni dal primo pagamento ti rimborsiamo tutto. Disdici con un clic quando vuoi.' },
]
</script>

<template>
  <Head>
    <title>Fusion Moduli: i tuoi moduli PDF in 30 secondi, e un tecnico te li imposta</title>
    <meta name="description" content="Carica il modulo PDF che compili ogni giorno: un tecnico te lo imposta in 30 minuti di videochiamata, poi lo compili in 30 secondi. 29 €/mese, rimborso entro 30 giorni.">
    <meta name="robots" content="noindex">
  </Head>

  <div class="min-h-screen bg-white text-gray-900">
    <LaunchBanner />
    <!-- Barra semplice: logo e accesso -->
    <header class="border-b border-gray-100">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <span class="flex items-center gap-2.5">
          <span class="w-8 h-8 rounded-lg bg-blue-700 flex items-center justify-center">
            <svg class="w-4 h-4" viewBox="0 0 18 18" fill="none">
              <g transform="translate(2.2, 2.7) scale(0.089)">
                <path fill="white" d="M 82 7 C 97 0 114 17 111 43 C 108 68 88 80 60 82 C 73 65 75 48 62 36 C 50 24 30 22 25 11 C 18 -2 32 -8 48 0 C 60 6 74 7 82 7 Z"/>
                <path fill="white" d="M 45 84 C 61 75 78 90 74 114 C 70 136 50 150 27 150 C 38 134 40 117 28 105 C 17 93 1 93 -2 79 C -6 63 8 52 23 57 C 34 61 43 77 45 84 Z"/>
              </g>
            </svg>
          </span>
          <span class="text-sm font-bold tracking-tight">Fusion Moduli</span>
        </span>
        <Link href="/login" class="text-sm text-gray-600 hover:text-gray-900">Accedi</Link>
      </div>
    </header>

    <!-- Promessa -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 pt-14 pb-12 lg:pt-20 grid gap-12 lg:grid-cols-2 lg:items-center">
      <div>
        <h1 class="text-4xl sm:text-5xl font-extrabold leading-tight tracking-tight">
          I tuoi moduli PDF compilati in 30 secondi.
        </h1>
        <p class="mt-5 text-lg text-gray-600 leading-relaxed">
          Basta Adobe col cliente che aspetta. Carichi il modulo che usi ogni giorno e
          <strong class="text-gray-900">un tecnico te lo imposta in 30 minuti di videochiamata</strong>. Da lì lo compili in pochi secondi.
        </p>
        <div class="mt-8 flex flex-col sm:flex-row sm:items-center gap-4">
          <Link href="/register" class="inline-flex justify-center items-center px-7 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors">
            Inizia ora
          </Link>
          <span class="text-sm text-gray-500">
            <template v-if="offertaAttiva"><strong class="text-gray-900">{{ prezzo }} €/mese</strong> (poi {{ offerta.prezzo_pieno }} €) · tecnico incluso · rimborso 30 giorni</template>
            <template v-else>{{ prezzo }} €/mese · tecnico incluso · rimborso entro 30 giorni</template>
          </span>
        </div>
      </div>
      <DemoVideo />
    </section>

    <!-- Come funziona -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 py-12 lg:py-20">
      <h2 class="text-3xl font-bold text-center">Come funziona</h2>
      <ol class="mt-10 grid gap-6 md:grid-cols-3">
        <li v-for="s in steps" :key="s.title" class="rounded-2xl bg-gray-50 p-6">
          <h3 class="text-lg font-semibold">{{ s.title }}</h3>
          <p class="mt-2 text-gray-600 leading-relaxed">{{ s.desc }}</p>
        </li>
      </ol>
    </section>

    <!-- Prezzo -->
    <section data-funnel="prezzo" class="bg-gray-50">
      <div class="max-w-xl mx-auto px-4 sm:px-6 py-12 lg:py-20">
        <div class="rounded-2xl bg-white border border-gray-200 p-8">
          <p class="text-sm font-medium text-gray-500">Fusion Moduli</p>
          <LaunchPrice class="mt-1" />
          <PlanHighlight class="mt-6" />
          <ul class="mt-5 space-y-2.5 text-gray-700">
            <li v-for="f in included" :key="f" class="flex gap-3">
              <svg class="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
              {{ f }}
            </li>
          </ul>
          <Link href="/register" class="mt-8 flex justify-center px-6 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors">
            Inizia ora
          </Link>
          <p class="mt-4 text-center text-sm text-gray-500">Soddisfatto o rimborsato entro 30 giorni · disdici con un clic</p>
          <p class="mt-3 text-center text-xs text-gray-400">{{ vatNote }}</p>
          <p class="mt-2 text-center text-xs text-gray-400">{{ fairUse }}</p>
        </div>
      </div>
    </section>

    <!-- Domande -->
    <section class="max-w-3xl mx-auto px-4 sm:px-6 py-12 lg:py-20">
      <h2 class="text-3xl font-bold text-center">Domande</h2>
      <dl class="mt-10 space-y-8">
        <div v-for="f in faqs" :key="f.q">
          <dt class="text-lg font-semibold">{{ f.q }}</dt>
          <dd class="mt-2 text-gray-600 leading-relaxed">{{ f.a }}</dd>
        </div>
      </dl>
      <div class="mt-12 text-center">
        <Link href="/register" class="inline-flex px-7 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-colors">
          Carica il tuo modulo
        </Link>
      </div>
    </section>

    <footer class="border-t border-gray-100">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 flex flex-wrap gap-4 justify-between text-sm text-gray-500">
        <span>Fusion Media di Luca Gabriele Pantea · P.IVA 13148540969</span>
        <span class="flex gap-5">
          <a href="/termini" class="hover:text-gray-900">Termini</a>
          <a href="https://fusionsoft.it/privacy" target="_blank" rel="noopener" class="hover:text-gray-900">Privacy</a>
        </span>
      </div>
    </footer>

    <a
      :href="whatsappUrl()"
      target="_blank"
      rel="noopener"
      class="fixed bottom-5 right-5 z-40 inline-flex items-center rounded-full bg-[#25D366] px-5 py-3 text-sm font-semibold text-white shadow-lg hover:brightness-95 transition"
    >
      Hai una domanda?
    </a>
  </div>
</template>
