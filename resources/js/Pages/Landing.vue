<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'

// ── Hero live demo ──────────────────────────────────────────────────────────
const demo = ref({ nome: 'Marco', cognome: 'Rossi', data: '15/10/2024', servizio: 'Visita di controllo' })
const demoNomeCognome = computed(() => {
  const n = demo.value.nome.trim() || '___'
  const c = demo.value.cognome.trim() || '___'
  return `${n} ${c}`
})

// ── Scroll-aware navbar ─────────────────────────────────────────────────────
const scrolled = ref(false)
const onScroll = () => { scrolled.value = window.scrollY > 24 }
onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))

// ── Mobile menu ─────────────────────────────────────────────────────────────
const menuOpen = ref(false)
const scrollTo = (id: string) => {
  menuOpen.value = false
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}

// ── Pricing toggle ──────────────────────────────────────────────────────────
const annual = ref(false)

// ── FAQ accordion ───────────────────────────────────────────────────────────
const openFaq = ref<number | null>(null)
const toggleFaq = (i: number) => { openFaq.value = openFaq.value === i ? null : i }

const benefits = [
  { title: 'Riduci gli errori', desc: 'I campi guidati eliminano dati mancanti, valori inconsistenti e correzioni manuali sul documento.', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
  { title: 'Compila più velocemente', desc: "L'interfaccia porta l'operatore da un campo all'altro senza dover leggere e interpretare il documento.", icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
  { title: 'Documenti uniformi', desc: 'Ogni documento esce con lo stesso formato e la stessa qualità, indipendentemente da chi lo ha compilato.', icon: 'M4 6h16M4 10h16M4 14h10M4 18h7' },
  { title: 'Onboarding più semplice', desc: 'Un nuovo collaboratore può compilare documenti correttamente fin dal primo giorno, senza formazione specifica.', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
  { title: 'Tutta la modulistica centralizzata', desc: 'Basta cartelle email, versioni diverse e moduli trovati per caso. Un unico posto per tutti i modelli.', icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z' },
  { title: 'Archivio sempre disponibile', desc: 'I documenti compilati vengono salvati automaticamente e sono scaricabili in qualsiasi momento.', icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4' },
]

const useCases = [
  {
    sector: 'Assicurazioni',
    desc: 'Agenzie e brokers che compilano polizze, schede cliente e dichiarazioni più volte al giorno.',
    docs: ['Moduli di polizza', 'Dichiarazioni sinistro', 'Schede cliente', 'Informative privacy'],
    icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
  },
  {
    sector: 'Studi professionali',
    desc: 'Studi legali, commercialisti e consulenti con documentazione standard da personalizzare per ogni cliente.',
    docs: ['Lettere di incarico', 'Consensi al trattamento dati', 'Procure', 'Verbali'],
    icon: 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
  },
  {
    sector: 'Beauty & Sanitario',
    desc: 'Centri estetici, ambulatori e studi medici che raccolgono consensi e dati anamnestici ad ogni visita.',
    docs: ['Consensi informati', 'Schede anamnestiche', 'Moduli trattamento', 'Informative GDPR'],
    icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
  },
  {
    sector: 'Aziende di servizi',
    desc: 'Imprese di manutenzione, installazione e assistenza con verbali e schede intervento da compilare sul campo.',
    docs: ['Contratti standard', 'Schede intervento', 'Verbali di consegna', 'Ordini di servizio'],
    icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
  },
]

const templates = [
  { name: 'Consenso informato', cat: 'Sanitario', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
  { name: 'Scheda cliente', cat: 'Commerciale', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
  { name: 'Verbale di riunione', cat: 'Interno', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2' },
  { name: 'Modulo assicurativo', cat: 'Assicurazioni', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
  { name: 'Dichiarazione', cat: 'Amministrativo', icon: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z' },
  { name: 'Contratto standard', cat: 'Legale', icon: 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z' },
]

const problemItems = [
  { icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', title: 'PDF compilati a mano', desc: 'Correzioni con bianchetto, grafia illeggibile, campi che non entrano nello spazio disponibile.' },
  { icon: 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z', title: 'Stessi dati riscritti più volte', desc: "Nome, data e codice fiscale copiati manualmente su ogni documento dell'incartamento." },
  { icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', title: 'Campi dimenticati o compilati male', desc: 'Documenti restituiti al mittente, firme mancanti, sezioni saltate per distrazione.' },
  { icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', title: 'Documenti non uniformi', desc: 'Ogni operatore usa una versione diversa del modulo. Nessuna coerenza tra uffici o colleghi.' },
]

const steps = [
  {
    num: '01',
    title: 'Scegli il modulo',
    desc: 'Apri la libreria dei tuoi modelli e seleziona il documento da compilare. Tutti i moduli della tua organizzazione sono in un unico posto.',
    icon: 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
  },
  {
    num: '02',
    title: 'Compila i dati',
    desc: "L'interfaccia ti guida campo per campo. Ogni dato inserito appare immediatamente nel documento finale, a destra dello schermo.",
    icon: 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z',
  },
  {
    num: '03',
    title: 'Genera il documento',
    desc: "Quando tutti i campi sono compilati, premi il pulsante e scarica il PDF completo, pronto per la firma o la stampa.",
    icon: 'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
  },
]

const planFeatures = [
  'Modelli illimitati',
  'Compilazioni illimitate',
  'Generazione PDF automatica',
  'Anteprima in tempo reale',
  'Archivio documenti',
  'Più utenti nella stessa organizzazione',
  'Accesso da browser, nessuna installazione',
  'Nessun costo di attivazione',
]

const previewPoints = [
  'Errori visibili subito, prima di stampare',
  'Nessun dubbio su come apparirà il documento finale',
  "L'operatore ha tutto in un'unica schermata",
  'Nuovi colleghi operativi fin dal primo giorno',
]

const faqs = [
  {
    q: 'Posso usare i miei documenti?',
    a: 'Sì. Puoi caricare i tuoi documenti esistenti e configurare i campi da compilare direttamente nel sistema. Non è necessario ricreare nulla da zero.',
  },
  {
    q: 'Posso modificare i moduli già caricati?',
    a: 'Sì. Ogni modello può essere aggiornato in qualsiasi momento: puoi aggiungere, rimuovere o modificare i campi senza dover ricaricare il documento.',
  },
  {
    q: 'Come viene generato il documento finale?',
    a: "Il sistema sovrappone i dati inseriti al documento originale e genera un file PDF completo, leggibile e pronto all'uso.",
  },
  {
    q: 'Posso utilizzare Fusion Moduli con più collaboratori?',
    a: "Sì. Più utenti possono lavorare nella stessa organizzazione. Ogni collaboratore accede con le proprie credenziali e vede i moduli dell'azienda.",
  },
  {
    q: 'I miei dati vengono salvati?',
    a: 'Sì. I documenti compilati vengono archiviati nel sistema e sono sempre accessibili. Puoi scaricarli in qualsiasi momento.',
  },
  {
    q: "Posso annullare l'abbonamento?",
    a: "Sì, puoi annullare in qualsiasi momento senza penali. L'accesso rimane attivo fino alla fine del periodo già pagato.",
  },
  {
    q: 'Serve installare qualcosa?',
    a: 'No. Fusion Moduli funziona completamente nel browser. Nessun software, nessun plugin, nessuna installazione.',
  },
]
</script>

<template>
  <Head>
    <title>Fusion Moduli — Compila e genera documenti più velocemente</title>
    <meta name="description" content="Digitalizza i moduli della tua azienda. Compila i dati attraverso procedure guidate, visualizza il documento in tempo reale e genera il file finale.">
    <meta property="og:title" content="Fusion Moduli — Compila e genera documenti più velocemente">
    <meta property="og:description" content="Digitalizza i moduli della tua azienda. Compila i dati attraverso procedure guidate, visualizza il documento in tempo reale e genera il file finale.">
  </Head>

  <!-- ════════════════════════════════════════════════════════════
       NAVBAR
  ════════════════════════════════════════════════════════════ -->
  <nav
    class="fixed top-0 inset-x-0 z-50 transition-all duration-200"
    :class="scrolled
      ? 'bg-white/95 backdrop-blur-md shadow-sm border-b border-gray-100'
      : 'bg-white border-b border-transparent'"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">

        <!-- Logo -->
        <a href="/" class="flex items-center gap-2.5 shrink-0">
          <div class="w-8 h-8 rounded-lg bg-blue-700 flex items-center justify-center">
            <svg class="w-4.5 h-4.5" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
              <g transform="translate(2.2, 2.7) scale(0.089)">
                <path fill="white" d="M 82 7 C 97 0 114 17 111 43 C 108 68 88 80 60 82 C 73 65 75 48 62 36 C 50 24 30 22 25 11 C 18 -2 32 -8 48 0 C 60 6 74 7 82 7 Z"/>
                <path fill="white" d="M 45 84 C 61 75 78 90 74 114 C 70 136 50 150 27 150 C 38 134 40 117 28 105 C 17 93 1 93 -2 79 C -6 63 8 52 23 57 C 34 61 43 77 45 84 Z"/>
              </g>
            </svg>
          </div>
          <span class="text-sm font-bold text-gray-900 tracking-tight">Fusion Moduli</span>
        </a>

        <!-- Desktop nav links -->
        <div class="hidden md:flex items-center gap-7">
          <button @click="scrollTo('come-funziona')" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">Come funziona</button>
          <button @click="scrollTo('funzionalita')" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">Funzionalità</button>
          <button @click="scrollTo('prezzi')" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">Prezzi</button>
          <button @click="scrollTo('faq')" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">FAQ</button>
        </div>

        <!-- Desktop right -->
        <div class="hidden md:flex items-center gap-3">
          <Link href="/login" class="text-sm text-gray-600 hover:text-gray-900 font-medium transition-colors px-2 py-1">Accedi</Link>
          <Link
            href="/register"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm"
          >Provalo gratis</Link>
        </div>

        <!-- Mobile burger -->
        <button
          @click="menuOpen = !menuOpen"
          class="md:hidden p-2 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors"
          aria-label="Menu"
        >
          <svg v-if="!menuOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>

      </div>
    </div>

    <!-- Mobile menu -->
    <div v-if="menuOpen" class="md:hidden border-t border-gray-100 bg-white px-4 py-4 space-y-1">
      <button @click="scrollTo('come-funziona')" class="block w-full text-left px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-lg">Come funziona</button>
      <button @click="scrollTo('funzionalita')" class="block w-full text-left px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-lg">Funzionalità</button>
      <button @click="scrollTo('prezzi')" class="block w-full text-left px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-lg">Prezzi</button>
      <button @click="scrollTo('faq')" class="block w-full text-left px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-lg">FAQ</button>
      <div class="pt-3 border-t border-gray-100 flex flex-col gap-2">
        <Link href="/login" class="block px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 rounded-lg font-medium">Accedi</Link>
        <Link href="/register" class="block px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg text-center hover:bg-indigo-700 transition-colors">Provalo gratis</Link>
      </div>
    </div>
  </nav>

  <!-- ════════════════════════════════════════════════════════════
       HERO
  ════════════════════════════════════════════════════════════ -->
  <section class="pt-24 pb-16 lg:pt-32 lg:pb-24 bg-white overflow-hidden">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="lg:grid lg:grid-cols-2 lg:gap-16 lg:items-center">

        <!-- Left: text -->
        <div class="max-w-xl">
          <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 border border-indigo-100 rounded-full text-xs font-medium text-indigo-700 mb-6">
            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 inline-block"></span>
            Nessuna carta di credito richiesta
          </div>

          <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 leading-tight tracking-tight">
            Trasforma i tuoi moduli in procedure digitali guidate.
          </h1>
          <p class="mt-5 text-lg text-gray-500 leading-relaxed">
            Inserisci i dati attraverso un'interfaccia pulita e guarda il documento
            aggiornarsi in tempo reale. Genera il file finale in un clic.
          </p>

          <div class="mt-8 flex flex-wrap gap-3">
            <Link
              href="/register"
              class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-sm transition-colors text-sm"
            >
              Provalo gratis
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
              </svg>
            </Link>
            <button
              @click="scrollTo('video-demo')"
              class="inline-flex items-center gap-2 px-6 py-3 bg-white border border-gray-200 hover:border-gray-300 text-gray-700 font-semibold rounded-lg transition-colors text-sm hover:bg-gray-50"
            >
              <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Guarda come funziona
            </button>
          </div>

          <p class="mt-4 text-xs text-gray-400">Prova gratuita · Nessun impegno · Disdici quando vuoi</p>
        </div>

        <!-- Right: interactive product demo -->
        <div class="mt-12 lg:mt-0">
          <!-- App chrome -->
          <div class="rounded-2xl border border-gray-200 shadow-2xl shadow-gray-200/60 overflow-hidden bg-white">
            <!-- Title bar -->
            <div class="flex items-center gap-2 px-4 py-3 bg-gray-50 border-b border-gray-100">
              <div class="flex gap-1.5">
                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                <div class="w-3 h-3 rounded-full bg-green-400"></div>
              </div>
              <div class="flex-1 mx-3 h-6 bg-gray-100 rounded-md flex items-center justify-center">
                <span class="text-xs text-gray-400">fusionmoduli.it · Compilazione</span>
              </div>
            </div>

            <!-- Two-panel layout -->
            <div class="grid grid-cols-2 divide-x divide-gray-100 min-h-64">

              <!-- Form panel -->
              <div class="p-5 bg-white">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Compila</p>
                <div class="space-y-3">
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">Nome</label>
                    <input
                      v-model="demo.nome"
                      type="text"
                      class="w-full border border-gray-200 rounded-md px-2.5 py-1.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-400 transition"
                      placeholder="Nome"
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">Cognome</label>
                    <input
                      v-model="demo.cognome"
                      type="text"
                      class="w-full border border-gray-200 rounded-md px-2.5 py-1.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-400 transition"
                      placeholder="Cognome"
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">Data</label>
                    <input
                      v-model="demo.data"
                      type="text"
                      class="w-full border border-gray-200 rounded-md px-2.5 py-1.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-400 transition"
                      placeholder="gg/mm/aaaa"
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">Servizio</label>
                    <select
                      v-model="demo.servizio"
                      class="w-full border border-gray-200 rounded-md px-2.5 py-1.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white transition"
                    >
                      <option>Visita di controllo</option>
                      <option>Consulenza</option>
                      <option>Trattamento estetico</option>
                      <option>Intervento chirurgico</option>
                    </select>
                  </div>
                </div>
                <button class="mt-4 w-full py-2 bg-indigo-600 text-white text-xs font-semibold rounded-md hover:bg-indigo-700 transition">
                  Genera PDF
                </button>
              </div>

              <!-- Document preview panel -->
              <div class="p-5 bg-gray-50/60">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-4">Anteprima</p>
                <div class="bg-white rounded-lg border border-gray-200 p-4 text-xs leading-relaxed shadow-sm">
                  <p class="font-bold text-gray-800 text-center mb-3 uppercase tracking-wide text-[10px]">Consenso informato</p>
                  <div class="border-t border-gray-100 pt-3 space-y-2 text-gray-700">
                    <p>
                      Il/La sottoscritto/a
                      <span class="font-semibold text-indigo-700">{{ demoNomeCognome }}</span>,
                    </p>
                    <p>
                      in data
                      <span class="font-semibold text-indigo-700">{{ demo.data || '___' }}</span>,
                    </p>
                    <p>
                      per il servizio di
                      <span class="font-semibold text-indigo-700">{{ demo.servizio || '___' }}</span>,
                    </p>
                    <p class="text-gray-400 leading-snug pt-1">
                      dichiara di aver ricevuto informazioni complete circa la procedura proposta e di esprimere il proprio consenso informato…
                    </p>
                    <div class="mt-4 pt-3 border-t border-gray-100">
                      <div class="flex justify-between text-gray-400">
                        <span>Data: <span class="text-gray-700">{{ demo.data || '___' }}</span></span>
                        <span>Firma: ____________</span>
                      </div>
                    </div>
                  </div>
                </div>
                <p class="mt-2 text-center text-[10px] text-indigo-400 font-medium">● aggiornamento in tempo reale</p>
              </div>

            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       VIDEO DEMO
  ════════════════════════════════════════════════════════════ -->
  <section id="video-demo" class="py-20 lg:py-28 bg-gray-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <p class="text-indigo-400 text-sm font-semibold uppercase tracking-widest mb-3">Demo</p>
      <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Guarda come funziona in meno di un minuto.</h2>
      <p class="text-gray-400 text-lg mb-10 max-w-xl mx-auto">
        Dal caricamento del documento alla generazione del PDF: tre passaggi, zero complicazioni.
      </p>

      <!-- Video placeholder — replace src with actual embed or <video> tag -->
      <div class="relative aspect-video rounded-2xl overflow-hidden bg-gray-800 border border-gray-700 shadow-2xl group cursor-pointer">
        <!-- Placeholder content -->
        <div class="absolute inset-0 flex flex-col items-center justify-center">
          <div class="w-16 h-16 rounded-full bg-white/10 border border-white/20 flex items-center justify-center group-hover:bg-white/20 transition-colors">
            <svg class="w-7 h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
              <path d="M8 5v14l11-7z"/>
            </svg>
          </div>
          <p class="mt-4 text-gray-400 text-sm">Video demo — disponibile a breve</p>
        </div>
        <!-- Replace this comment with an <iframe> or <video> when the recording is ready:
             <iframe src="https://..." class="absolute inset-0 w-full h-full" frameborder="0" allowfullscreen></iframe>
        -->
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       PROBLEMA
  ════════════════════════════════════════════════════════════ -->
  <section class="py-20 lg:py-28 bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-14">
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Compilare documenti non dovrebbe richiedere tutto questo tempo.</h2>
        <p class="mt-4 text-lg text-gray-500 max-w-2xl mx-auto">
          Eppure ogni giorno nelle aziende si perde tempo su attività che potrebbero essere eliminate.
        </p>
      </div>

      <div class="grid sm:grid-cols-2 gap-5">
        <div v-for="(item, i) in problemItems" :key="i"
          class="flex gap-4 p-5 bg-white rounded-xl border border-gray-200"
        >
          <div class="shrink-0 w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="item.icon"/>
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 text-sm mb-1">{{ item.title }}</h3>
            <p class="text-sm text-gray-500">{{ item.desc }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       SOLUZIONE — COME FUNZIONA
  ════════════════════════════════════════════════════════════ -->
  <section id="come-funziona" class="py-20 lg:py-28 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16">
        <p class="text-indigo-600 text-sm font-semibold uppercase tracking-widest mb-3">Come funziona</p>
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Tre passaggi. Nessuna complicazione.</h2>
        <p class="mt-4 text-lg text-gray-500 max-w-xl mx-auto">
          Dal documento grezzo al PDF compilato in pochi minuti.
        </p>
      </div>

      <div class="grid md:grid-cols-3 gap-8">
        <div v-for="(step, i) in steps" :key="i"
          class="relative flex flex-col"
        >
          <!-- Number badge -->
          <div class="flex items-center gap-3 mb-5">
            <span class="text-4xl font-black text-gray-100">{{ step.num }}</span>
            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="step.icon"/>
              </svg>
            </div>
          </div>
          <h3 class="text-lg font-bold text-gray-900 mb-2">{{ step.title }}</h3>
          <p class="text-gray-500 text-sm leading-relaxed">{{ step.desc }}</p>

          <!-- Connector arrow (desktop only) -->
          <div v-if="i < 2" class="hidden md:block absolute top-10 -right-4 z-10">
            <svg class="w-8 h-8 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       ANTEPRIMA LIVE (feature callout)
  ════════════════════════════════════════════════════════════ -->
  <section id="funzionalita" class="py-20 lg:py-28 bg-indigo-50/60">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="lg:grid lg:grid-cols-2 lg:gap-16 lg:items-center">

        <!-- Left: explanation -->
        <div>
          <p class="text-indigo-600 text-sm font-semibold uppercase tracking-widest mb-3">Anteprima in tempo reale</p>
          <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-5">
            Vedi il documento mentre lo compili.
          </h2>
          <p class="text-lg text-gray-600 mb-8 leading-relaxed">
            Ogni dato inserito appare immediatamente nel documento finale. L'operatore vede
            l'esatto risultato prima ancora di generare il PDF.
          </p>
          <ul class="space-y-4">
            <li v-for="item in previewPoints" :key="item" class="flex items-start gap-3">
              <span class="mt-0.5 shrink-0 w-5 h-5 rounded-full bg-indigo-600 flex items-center justify-center">
                <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
              </span>
              <span class="text-gray-700 text-sm">{{ item }}</span>
            </li>
          </ul>
        </div>

        <!-- Right: mini demo -->
        <div class="mt-10 lg:mt-0">
          <div class="rounded-2xl border border-indigo-100 shadow-xl bg-white overflow-hidden">
            <div class="bg-gray-50 border-b border-gray-100 px-4 py-3 flex items-center gap-3">
              <div class="flex gap-1.5">
                <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-yellow-400"></div>
                <div class="w-2.5 h-2.5 rounded-full bg-green-400"></div>
              </div>
              <span class="text-xs text-gray-400 font-medium">Scheda cliente — in compilazione</span>
            </div>
            <div class="grid grid-cols-2 divide-x divide-gray-100">
              <div class="p-4 space-y-3">
                <div class="h-3 bg-gray-100 rounded w-16 mb-1"></div>
                <div class="h-8 bg-indigo-50 border border-indigo-200 rounded-md flex items-center px-3">
                  <span class="text-xs text-indigo-700 font-medium">Bianchi Costruzioni Srl</span>
                </div>
                <div class="h-3 bg-gray-100 rounded w-12 mb-1"></div>
                <div class="h-8 bg-gray-50 border border-gray-200 rounded-md flex items-center px-3">
                  <span class="text-xs text-gray-400">Partita IVA…</span>
                </div>
                <div class="h-3 bg-gray-100 rounded w-20 mb-1"></div>
                <div class="h-8 bg-indigo-50 border border-indigo-200 rounded-md flex items-center px-3">
                  <span class="text-xs text-indigo-700 font-medium">Milano, Via Roma 12</span>
                </div>
                <div class="mt-3 h-7 bg-indigo-600 rounded-md flex items-center justify-center">
                  <span class="text-xs text-white font-semibold">Genera PDF</span>
                </div>
              </div>
              <div class="p-4 bg-gray-50/60">
                <div class="bg-white rounded-lg border border-gray-200 p-3 text-xs shadow-sm">
                  <div class="text-center mb-2">
                    <div class="h-2 bg-gray-800 rounded w-24 mx-auto mb-1"></div>
                    <div class="h-1.5 bg-gray-300 rounded w-16 mx-auto"></div>
                  </div>
                  <div class="border-t border-gray-100 pt-2 space-y-1.5">
                    <div class="flex gap-1.5 items-baseline">
                      <span class="text-gray-400 text-[10px] shrink-0">Cliente:</span>
                      <span class="text-indigo-700 font-semibold text-[10px]">Bianchi Costruzioni Srl</span>
                    </div>
                    <div class="flex gap-1.5 items-baseline">
                      <span class="text-gray-400 text-[10px] shrink-0">P.IVA:</span>
                      <span class="text-gray-300 text-[10px]">—</span>
                    </div>
                    <div class="flex gap-1.5 items-baseline">
                      <span class="text-gray-400 text-[10px] shrink-0">Sede:</span>
                      <span class="text-indigo-700 font-semibold text-[10px]">Milano, Via Roma 12</span>
                    </div>
                    <div class="mt-2 space-y-1">
                      <div class="h-1.5 bg-gray-100 rounded w-full"></div>
                      <div class="h-1.5 bg-gray-100 rounded w-3/4"></div>
                      <div class="h-1.5 bg-gray-100 rounded w-5/6"></div>
                    </div>
                  </div>
                </div>
                <p class="mt-1.5 text-center text-[9px] text-indigo-400 font-medium">● aggiornamento automatico</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       TEMPLATE
  ════════════════════════════════════════════════════════════ -->
  <section class="py-20 lg:py-28 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <p class="text-indigo-600 text-sm font-semibold uppercase tracking-widest mb-3">Modulistica</p>
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Tutti i tuoi modelli in un unico posto.</h2>
        <p class="mt-4 text-gray-500 text-lg max-w-xl mx-auto">
          Ogni azienda può configurare e gestire i propri documenti. Aggiungi un modello e
          rendilo disponibile a tutti i collaboratori in pochi minuti.
        </p>
      </div>

      <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <div v-for="(tpl, i) in templates" :key="i"
          class="group p-5 rounded-xl border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all cursor-pointer bg-white"
        >
          <div class="w-9 h-9 rounded-lg bg-indigo-50 group-hover:bg-indigo-100 transition-colors flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="tpl.icon"/>
            </svg>
          </div>
          <p class="font-semibold text-gray-900 text-sm leading-snug">{{ tpl.name }}</p>
          <p class="text-xs text-gray-400 mt-0.5">{{ tpl.cat }}</p>
        </div>
      </div>

      <p class="mt-8 text-center text-sm text-gray-500">
        E molti altri — ogni organizzazione può creare modelli personalizzati per le proprie procedure.
      </p>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       BENEFICI
  ════════════════════════════════════════════════════════════ -->
  <section class="py-20 lg:py-28 bg-gray-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-14">
        <p class="text-indigo-600 text-sm font-semibold uppercase tracking-widest mb-3">Vantaggi concreti</p>
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Meno errori. Più velocità. Più controllo.</h2>
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="b in benefits" :key="b.title"
          class="flex gap-4 p-5 bg-white rounded-xl border border-gray-200"
        >
          <div class="shrink-0 w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center">
            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="b.icon"/>
            </svg>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 text-sm mb-1">{{ b.title }}</h3>
            <p class="text-sm text-gray-500">{{ b.desc }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       USE CASES
  ════════════════════════════════════════════════════════════ -->
  <section class="py-20 lg:py-28 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-14">
        <p class="text-indigo-600 text-sm font-semibold uppercase tracking-widest mb-3">Chi lo usa</p>
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Adatto a qualsiasi settore che gestisce documentazione ricorrente.</h2>
      </div>

      <div class="grid sm:grid-cols-2 gap-6">
        <div v-for="uc in useCases" :key="uc.sector"
          class="p-6 rounded-xl border border-gray-200 hover:border-indigo-200 transition-colors"
        >
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="uc.icon"/>
              </svg>
            </div>
            <h3 class="font-bold text-gray-900">{{ uc.sector }}</h3>
          </div>
          <p class="text-sm text-gray-500 mb-4">{{ uc.desc }}</p>
          <ul class="space-y-1.5">
            <li v-for="d in uc.docs" :key="d" class="flex items-center gap-2 text-sm text-gray-700">
              <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 shrink-0"></span>
              {{ d }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       PRICING
  ════════════════════════════════════════════════════════════ -->
  <section id="prezzi" class="py-20 lg:py-28 bg-gray-50">
    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-10">
        <p class="text-indigo-600 text-sm font-semibold uppercase tracking-widest mb-3">Prezzi</p>
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Semplice. Senza sorprese.</h2>
        <p class="mt-3 text-gray-500">Un piano. Tutto incluso. Nessun costo di attivazione.</p>
      </div>

      <!-- Toggle -->
      <div class="flex items-center justify-center gap-3 mb-8">
        <span :class="!annual ? 'text-gray-900 font-semibold' : 'text-gray-400'" class="text-sm transition-colors">Mensile</span>
        <button
          @click="annual = !annual"
          :class="annual ? 'bg-indigo-600' : 'bg-gray-200'"
          class="relative w-11 h-6 rounded-full transition-colors focus:outline-none"
          aria-label="Alterna fatturazione annuale"
        >
          <span
            :class="annual ? 'translate-x-5' : 'translate-x-1'"
            class="absolute top-1 left-0 w-4 h-4 rounded-full bg-white shadow transition-transform"
          ></span>
        </button>
        <span :class="annual ? 'text-gray-900 font-semibold' : 'text-gray-400'" class="text-sm transition-colors">
          Annuale
          <span class="ml-1.5 inline-block px-1.5 py-0.5 text-[10px] font-bold bg-green-100 text-green-700 rounded-full">-2 mesi</span>
        </span>
      </div>

      <!-- Pricing card -->
      <div class="bg-white rounded-2xl border border-gray-200 shadow-lg p-8">
        <div class="flex items-start justify-between mb-6">
          <div>
            <h3 class="text-lg font-bold text-gray-900">Fusion Moduli Pro</h3>
            <p class="text-sm text-gray-500 mt-0.5">Per team e professionisti</p>
          </div>
          <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
          </div>
        </div>

        <div class="flex items-baseline gap-2 mb-1">
          <span class="text-5xl font-extrabold text-gray-900">
            {{ annual ? '€24' : '€29' }}
          </span>
          <span class="text-gray-500 text-sm">/mese</span>
        </div>
        <p v-if="annual" class="text-sm text-green-600 font-medium mb-6">
          Fatturato €290/anno · Risparmi €58 rispetto al piano mensile
        </p>
        <p v-else class="text-sm text-gray-400 mb-6">
          Oppure €290/anno — equivale a circa 2 mesi gratuiti
        </p>

        <Link
          href="/register"
          class="block w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-center text-sm transition-colors shadow-sm mb-6"
        >
          Inizia gratis
        </Link>

        <ul class="space-y-3">
          <li v-for="f in planFeatures" :key="f" class="flex items-center gap-3 text-sm text-gray-700">
            <span class="w-4 h-4 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
              <svg class="w-2.5 h-2.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
              </svg>
            </span>
            {{ f }}
          </li>
        </ul>
      </div>

      <p class="text-center text-xs text-gray-400 mt-5">
        Disdici in qualsiasi momento. Nessuna penale.
      </p>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       FAQ
  ════════════════════════════════════════════════════════════ -->
  <section id="faq" class="py-20 lg:py-28 bg-white">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <p class="text-indigo-600 text-sm font-semibold uppercase tracking-widest mb-3">FAQ</p>
        <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">Domande frequenti</h2>
      </div>

      <div class="divide-y divide-gray-100 border border-gray-100 rounded-2xl overflow-hidden">
        <div v-for="(faq, i) in faqs" :key="i">
          <button
            @click="toggleFaq(i)"
            class="w-full flex items-center justify-between px-6 py-5 text-left hover:bg-gray-50 transition-colors"
          >
            <span class="font-semibold text-gray-900 text-sm pr-4">{{ faq.q }}</span>
            <span class="shrink-0 w-5 h-5 rounded-full border border-gray-200 flex items-center justify-center transition-transform" :class="openFaq === i ? 'rotate-180' : ''">
              <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
            </span>
          </button>
          <div v-if="openFaq === i" class="px-6 pb-5">
            <p class="text-sm text-gray-600 leading-relaxed">{{ faq.a }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       FINAL CTA
  ════════════════════════════════════════════════════════════ -->
  <section class="py-20 lg:py-28 bg-indigo-600">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">
        Smetti di compilare documenti a mano.
      </h2>
      <p class="text-indigo-200 text-lg mb-10 max-w-xl mx-auto leading-relaxed">
        Trasforma i moduli che usi ogni giorno in procedure semplici e guidate.
        Il tuo team li compilerà correttamente al primo tentativo.
      </p>
      <Link
        href="/register"
        class="inline-flex items-center gap-2.5 px-8 py-4 bg-white hover:bg-gray-50 text-indigo-700 font-bold rounded-xl text-sm shadow-lg transition-colors"
      >
        Provalo gratis
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </Link>
      <p class="mt-4 text-indigo-300 text-sm">Nessuna carta di credito · Disdici quando vuoi</p>
    </div>
  </section>

  <!-- ════════════════════════════════════════════════════════════
       FOOTER
  ════════════════════════════════════════════════════════════ -->
  <footer class="bg-gray-900 text-gray-400 py-14">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid sm:grid-cols-3 gap-10 mb-10">

        <!-- Brand -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <div class="w-7 h-7 rounded-lg bg-blue-700 flex items-center justify-center">
              <svg class="w-4 h-4" viewBox="0 0 18 18" fill="none">
                <g transform="translate(2.2, 2.7) scale(0.089)">
                  <path fill="white" d="M 82 7 C 97 0 114 17 111 43 C 108 68 88 80 60 82 C 73 65 75 48 62 36 C 50 24 30 22 25 11 C 18 -2 32 -8 48 0 C 60 6 74 7 82 7 Z"/>
                  <path fill="white" d="M 45 84 C 61 75 78 90 74 114 C 70 136 50 150 27 150 C 38 134 40 117 28 105 C 17 93 1 93 -2 79 C -6 63 8 52 23 57 C 34 61 43 77 45 84 Z"/>
                </g>
              </svg>
            </div>
            <span class="text-white font-bold text-sm">Fusion Moduli</span>
          </div>
          <p class="text-sm text-gray-500 max-w-xs">
            Procedure guidate per compilare documenti senza errori e senza perdere tempo.
          </p>
        </div>

        <!-- Product links -->
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-gray-600 mb-4">Prodotto</p>
          <ul class="space-y-2.5 text-sm">
            <li><button @click="scrollTo('come-funziona')" class="hover:text-white transition-colors">Come funziona</button></li>
            <li><button @click="scrollTo('funzionalita')" class="hover:text-white transition-colors">Funzionalità</button></li>
            <li><button @click="scrollTo('prezzi')" class="hover:text-white transition-colors">Prezzi</button></li>
            <li><button @click="scrollTo('faq')" class="hover:text-white transition-colors">FAQ</button></li>
          </ul>
        </div>

        <!-- Account links -->
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-gray-600 mb-4">Account</p>
          <ul class="space-y-2.5 text-sm">
            <li><Link href="/login" class="hover:text-white transition-colors">Accedi</Link></li>
            <li><Link href="/register" class="hover:text-white transition-colors">Inizia gratis</Link></li>
          </ul>
        </div>

      </div>

      <div class="border-t border-gray-800 pt-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <p>© {{ new Date().getFullYear() }} Fusion Moduli. Tutti i diritti riservati.</p>
        <div class="flex gap-5">
          <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
          <a href="#" class="hover:text-white transition-colors">Termini di servizio</a>
        </div>
      </div>
    </div>
  </footer>

</template>
