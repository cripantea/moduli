<script setup lang="ts">
import FusionsoftBadge from '@/Components/FusionsoftBadge.vue'
import { whatsappUrl } from '@/support'
import { Head, Link } from '@inertiajs/vue3'
import { motion, MotionConfig } from 'motion-v'
import { computed, onMounted, onUnmounted, ref } from 'vue'

/*
 * Landing di Fusion Moduli. Idea: la riga vuota di un modulo italiano ("Nome ________")
 * entra nel titolo e si compila da sola; più giù il visitatore scrive un nome e lo vede finire sul modulo.
 * Direzione "La frase è il layout" (tasteskill, seed 808578): Bodoni Moda (Parma) + Jost, campo carminio come l'inchiostro dei timbri.
 */

// ── Riga del titolo che si compila da sola ───────────────────────────────────
const heroNames = ['Mario Rossi', 'Giulia Bianchi', 'Ferri Srl']
const heroTyped = ref('')
let heroTimer: number | undefined

function typeLoop(i = 0, pos = 0, deleting = false) {
  const name = heroNames[i % heroNames.length]
  heroTyped.value = name.slice(0, pos)
  let next: number
  if (!deleting && pos === name.length) { deleting = true; next = 1800 }
  else if (deleting && pos === 0) { deleting = false; i++; next = 400 }
  else { pos += deleting ? -1 : 1; next = deleting ? 45 : 95 }
  heroTimer = window.setTimeout(() => typeLoop(i, pos, deleting), next)
}

// ── La cosa viva: scrivi un nome e lo vedi sul modulo ────────────────────────
const examples = ['Giulia Bianchi', 'Officina Ferri Srl', 'Dott. Paolo Neri']
const typed = ref('Mario Rossi')
const today = new Date().toLocaleDateString('it-IT')
const parts = computed(() => {
  const t = typed.value.trim()
  const i = t.lastIndexOf(' ')
  return i > 0 ? { nome: t.slice(0, i), cognome: t.slice(i + 1) } : { nome: t, cognome: '' }
})

// ── Intestazione che prende l'inchiostro del capitolo sotto ──────────────────
const headerInk = ref<'paper' | 'carmine'>('paper')
let observer: IntersectionObserver | undefined
let footObserver: IntersectionObserver | undefined
// Sul footer c'è già il pulsante WhatsApp: la chat galleggiante si toglie di mezzo.
const footerInView = ref(false)

onMounted(() => {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) heroTyped.value = heroNames[0]
  else typeLoop()

  observer = new IntersectionObserver(
    (entries) => {
      for (const e of entries) {
        if (e.isIntersecting) headerInk.value = (e.target as HTMLElement).dataset.ink as 'paper' | 'carmine'
      }
    },
    { rootMargin: '0px 0px -95% 0px' },
  )
  document.querySelectorAll<HTMLElement>('[data-ink]').forEach((el) => observer!.observe(el))

  footObserver = new IntersectionObserver(([e]) => { footerInView.value = e.isIntersecting }, { threshold: 0.05 })
  const foot = document.querySelector('.foot')
  if (foot) footObserver.observe(foot)
})

onUnmounted(() => {
  window.clearTimeout(heroTimer)
  observer?.disconnect()
  footObserver?.disconnect()
})

// Entrata delle parti di ogni sezione: sale di 24px con l'opacità, una volta sola.
const rise = (delay = 0) => ({
  initial: { opacity: 0, y: 24 },
  whileInView: { opacity: 1, y: 0 },
  inViewOptions: { once: true, amount: 0.25 },
  transition: { duration: 0.7, delay, ease: [0.22, 1, 0.36, 1] },
})

const stages = [
  { time: 'Un minuto', grow: 60, clause: 'Prima carichi il PDF che compili più spesso, così com’è.' },
  { time: '40 secondi', grow: 40, clause: 'Poi l’AI legge il modulo e trova ogni campo da compilare.' },
  { time: 'Due minuti', grow: 120, clause: 'Infine controlli le righe sul PDF e le sposti se serve.' },
]

const faqs = [
  { q: 'Funziona con i miei moduli?', a: 'Sì: carichi il PDF che usi già, così com’è. Deleghe, denunce, richieste, contratti, schede cliente. Non devi ricreare niente.' },
  { q: 'E se l’AI sbaglia un campo?', a: 'Dopo la lettura vedi il PDF con una linea per ogni campo: la trascini sulla riga giusta, rinomini o togli i campi. Lo fai una volta sola.' },
  { q: 'Quanto ci metto ogni volta?', a: 'Scrivi i dati in un modulo semplice e guardi il PDF riempirsi. Per un modulo di una pagina bastano trenta secondi.' },
  { q: 'Dove finiscono i documenti?', a: 'Ogni compilazione resta in archivio, con ricerca: la ritrovi e la riscarichi quando vuoi. I file sono visibili solo alla tua azienda.' },
  { q: 'Posso disdire quando voglio?', a: 'Sì, con un clic da “Abbonamento” nell’app, senza penali. Ed entro 30 giorni dal primo pagamento ti rimborsiamo tutto.' },
  { q: 'Serve installare qualcosa?', a: 'No, funziona nel browser. Meglio dal computer: l’editor dei campi vuole uno schermo grande.' },
]
</script>

<template>
  <Head>
    <title>Fusion Moduli: compila i tuoi moduli PDF in 30 secondi, senza Adobe</title>
    <meta name="description" content="Carica il modulo PDF che compili ogni giorno: l'AI trova i campi, tu lo compili in 30 secondi e ogni documento resta in archivio. 29 €/mese, rimborso entro 30 giorni.">
    <meta property="og:title" content="Fusion Moduli: compila i tuoi moduli PDF in 30 secondi, senza Adobe">
    <meta property="og:description" content="Carica il modulo PDF che compili ogni giorno: l'AI trova i campi, tu lo compili in 30 secondi e ogni documento resta in archivio.">
    <meta property="og:url" content="https://moduli.fusionsoft.it/">
    <link rel="canonical" href="https://moduli.fusionsoft.it/">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,500;1,6..96,500&family=Jost:wght@400;500&display=swap" rel="stylesheet">
  </Head>

  <MotionConfig reduced-motion="user">
    <div class="lp">
      <!-- Intestazione: prende l'inchiostro del capitolo che passa sotto -->
      <header class="lp-bar" :class="`lp-bar--${headerInk}`">
        <a href="/" class="lp-word">Fusion Moduli</a>
        <nav class="lp-links" aria-label="Sezioni">
          <a href="#come-funziona">Come funziona</a>
          <a href="#prezzo">Prezzo</a>
          <a href="#domande">Domande</a>
          <Link href="/login">Accedi</Link>
        </nav>
        <Link href="/register" class="lp-btn lp-btn--bar">Carica il tuo modulo</Link>
      </header>

      <!-- Hero: due campi, carta sopra e carminio sotto; il titolo attraversa la linea -->
      <section class="hero" data-ink="paper">
        <div class="hero-field" aria-hidden="true" />
        <h1 class="hero-title">
          <span class="sr-only">Il modulo compilato in 30 secondi</span>
          <span v-for="layer in ['top', 'bottom']" :key="layer" class="hero-layer" :class="`hero-layer--${layer}`" aria-hidden="true">
            <span class="hero-line">Il modulo</span>
            <span class="hero-line hero-line--slot">
              <span class="slot" data-ui>
                <span class="slot-label">Nome e cognome</span>
                <span class="slot-value">{{ heroTyped }}<i class="caret" /></span>
              </span>
              <span>in 30 secondi</span>
            </span>
          </span>
        </h1>
        <span class="slot slot--phone" data-ui aria-hidden="true">
          <span class="slot-label">Nome e cognome</span>
          <span class="slot-value">{{ heroTyped }}<i class="caret" /></span>
        </span>
        <motion.div class="hero-foot" v-bind="rise(0.3)">
          <p>Carichi una volta il PDF che compili ogni giorno. L’AI trova i campi, tu scrivi i dati e scarichi il documento. Niente più Adobe col cliente che aspetta.</p>
          <motion.div :while-hover="{ y: -2 }" :while-press="{ scale: 0.97 }" class="inline-block">
            <Link href="/register" class="lp-btn lp-btn--pink">Carica il tuo modulo</Link>
          </motion.div>
          <span class="hero-note">29 € al mese, IVA inclusa · rimborso entro 30 giorni</span>
        </motion.div>
      </section>

      <!-- Il problema: titolo che sconfina sulla fotografia dei moduli di carta -->
      <section class="intro" data-ink="paper">
        <div class="intro-grid">
          <div class="intro-text">
            <motion.h2 class="intro-title" v-bind="rise()">Adobe, un campo<br>alla volta.</motion.h2>
            <motion.p class="intro-lead" v-bind="rise(0.08)">
              Apri il PDF, clicchi ogni riga, il testo non entra, rimpicciolisci, salvi, rinomini il file.
              Il giorno dopo, stesso modulo, tutto da capo. E il cliente intanto è lì davanti.
            </motion.p>
            <motion.figure class="intro-small" v-bind="rise(0.16)">
              <img src="/images/landing/rvk-mun-arch-file-cabinets-2011.jpg" width="1920" height="2891" loading="lazy" alt="Corridoio di un archivio con scaffali compattabili e scatole numerate">
              <figcaption>Con Moduli l’archivio è uno solo: ogni compilazione, cercabile.</figcaption>
            </motion.figure>
          </div>
          <motion.figure class="intro-photo" v-bind="rise(0.1)">
            <img src="/images/landing/legal-contract-signature-warm-tones.jpg" width="1920" height="1440" loading="lazy" alt="Contratti stampati sul tavolo con le righe vuote per nome e firma e una penna stilografica">
          </motion.figure>
        </div>
      </section>

      <!-- Come funziona: la barra di avanzamento, ingrandita -->
      <section id="come-funziona" class="how" data-ink="paper">
        <motion.h2 class="how-title" v-bind="rise()">Tre minuti, una volta sola.</motion.h2>
        <div class="how-bar" data-ui>
          <motion.div
            v-for="(s, i) in stages"
            :key="s.time"
            class="how-seg"
            :class="`how-seg--${i}`"
            :style="{ flexGrow: s.grow }"
            v-bind="rise(0.1 + i * 0.09)"
          >
            <span class="how-time">{{ s.time }}</span>
            <span class="how-clause">{{ s.clause }}</span>
          </motion.div>
        </div>
        <motion.p class="how-after" v-bind="rise(0.4)">Da lì in poi, ogni volta: trenta secondi a modulo.</motion.p>
      </section>

      <!-- La cosa viva: il titolo è un campo da compilare -->
      <section class="show" data-ink="carmine">
        <motion.h2 class="show-title" v-bind="rise()">Prova: scrivi un nome.</motion.h2>
        <label class="sr-only" for="prova-nome">Nome e cognome da mettere sul modulo</label>
        <div class="show-input" data-ui>
          <input id="prova-nome" v-model="typed" type="text" maxlength="40" autocomplete="off" spellcheck="false">
        </div>
        <div class="show-answer">
          <motion.div class="paper" data-ui v-bind="rise(0.1)">
            <p class="paper-head">MODULO DI RICHIESTA SERVIZIO</p>
            <p class="paper-row">Il/La sottoscritto/a: Nome <span class="fill fill--l">{{ parts.nome }}</span> Cognome <span class="fill fill--m">{{ parts.cognome }}</span></p>
            <p class="paper-row">Nato/a a <span class="fill fill--m">Milano</span> il <span class="fill fill--s">12/03/1980</span></p>
            <p class="paper-row">Richiede il seguente servizio: <span class="fill fill--l">Rinnovo pratica</span></p>
            <p class="paper-row">Data <span class="fill fill--s">{{ today }}</span> &nbsp; Firma <span class="fill fill--m" /></p>
          </motion.div>
          <motion.div class="archive" data-ui v-bind="rise(0.2)">
            <p class="archive-head">Archivio</p>
            <div class="archive-row archive-row--new">
              <span>Richiesta servizio, {{ typed.trim() || '…' }}</span><span>oggi</span><span class="archive-dl">Scarica</span>
            </div>
            <div class="archive-row"><span>Delega, Luca Verdi</span><span>ieri</span><span class="archive-dl">Scarica</span></div>
            <div class="archive-row"><span>Denuncia sinistro, Anna Costa</span><span>2 giorni fa</span><span class="archive-dl">Scarica</span></div>
          </motion.div>
        </div>
        <div class="show-examples">
          <button v-for="e in examples" :key="e" type="button" @click="typed = e">{{ e }}</button>
        </div>
      </section>

      <!-- Prezzo: tutta l'offerta in una frase -->
      <section id="prezzo" class="price" data-ink="paper">
        <motion.h2 class="price-sentence" v-bind="rise()">
          Tutto incluso, <span class="price-block">29 €</span> al mese.
        </motion.h2>
        <div class="price-foot">
          <motion.p v-bind="rise(0.1)">
            IVA inclusa. Moduli e compilazioni illimitati, l’AI che legge ogni nuovo PDF, l’archivio e una videochiamata
            di onboarding con noi. <strong>Soddisfatto o rimborsato entro 30 giorni</strong>, disdetta con un clic.
          </motion.p>
          <motion.div v-bind="rise(0.2)" class="price-action">
            <motion.div :while-hover="{ y: -2 }" :while-press="{ scale: 0.97 }" class="inline-block">
              <Link href="/register" class="lp-btn lp-btn--carmine">Carica il tuo modulo</Link>
            </motion.div>
          </motion.div>
        </div>
      </section>

      <!-- Domande: tutte aperte, domanda grande e risposta accanto -->
      <section id="domande" class="faq" data-ink="paper">
        <div class="faq-row">
          <h2 class="faq-q faq-q--soft">Domande frequenti</h2>
        </div>
        <motion.div v-for="(f, i) in faqs" :key="f.q" class="faq-row" v-bind="rise(i * 0.04)">
          <h3 class="faq-q">{{ f.q }}</h3>
          <p class="faq-a">{{ f.a }}</p>
        </motion.div>
      </section>

      <!-- Chiusura: l'idea di nuovo, a colori invertiti -->
      <section class="cta" data-ink="paper">
        <motion.h2 class="cta-title" v-bind="rise()">Il prossimo,<br>in 30 secondi.</motion.h2>
        <div class="cta-foot">
          <p>Carica il PDF che compili più spesso: in tre minuti è pronto, da domani ci metti trenta secondi.</p>
          <motion.div :while-hover="{ y: -2 }" :while-press="{ scale: 0.97 }" class="inline-block cta-action">
            <Link href="/register" class="lp-btn lp-btn--carmine">Carica il tuo modulo</Link>
          </motion.div>
        </div>
      </section>

      <!-- Footer: invito e nome tagliato dal bordo -->
      <footer class="foot" data-ink="carmine">
        <div class="foot-top">
          <div>
            <h2 class="foot-title">Hai una domanda?<br>Scrivici.</h2>
            <a :href="whatsappUrl('Ciao, ho una domanda su Fusion Moduli')" target="_blank" rel="noopener" class="lp-btn lp-btn--pink">Scrivici su WhatsApp</a>
          </div>
          <ul class="foot-links">
            <li><Link href="/login">Accedi</Link></li>
            <li><Link href="/register">Crea un account</Link></li>
            <li><a href="/termini">Termini di servizio</a></li>
            <li><a href="https://fusionsoft.it/privacy" target="_blank" rel="noopener">Privacy</a></li>
          </ul>
        </div>
        <div class="foot-legal">
          <FusionsoftBadge dark />
          <p class="foot-credits">
            Foto: <a href="https://commons.wikimedia.org/w/index.php?curid=15664522">“Rvk mun arch file cabinets 2011”</a> di Jabbi, CC0;
            <a href="https://commons.wikimedia.org/w/index.php?curid=55666535">“Legal Contract &amp; Signature - Warm Tones”</a> di Blogtrepreneur,
            <a href="https://creativecommons.org/licenses/by/2.0/">CC BY 2.0</a>, via Wikimedia Commons.
          </p>
        </div>
        <p class="foot-word" aria-hidden="true">Moduli</p>
      </footer>

      <a :href="whatsappUrl()" target="_blank" rel="noopener" class="lp-chat" :class="{ 'lp-chat--away': footerInView }" :aria-hidden="footerInView" :tabindex="footerInView ? -1 : 0">Hai una domanda?</a>
    </div>
  </MotionConfig>
</template>

<style scoped>
.lp {
  --carmine: #9c1220;
  --carmine-deep: #8a101c;
  --paper: #ffffff;
  --paper-2: #f6f1f1;
  --ink: #1d1a1a;
  --pink: #ffd9dc;
  --muted-pink: #f0b9bd;
  --charcoal: #2a2a2a;
  --serif: 'Bodoni Moda', 'Didot', Georgia, serif;
  --sans: 'Jost', system-ui, sans-serif;
  --margin: clamp(20px, 3vw, 40px);
  --step-small: clamp(0.8125rem, 0.79rem + 0.1vw, 0.875rem);
  --step-body: clamp(1.0625rem, 1rem + 0.25vw, 1.1875rem);
  --step-lead: clamp(1.25rem, 1.1rem + 0.7vw, 1.625rem);
  --step-head: clamp(2.25rem, 1.3rem + 3.6vw, 4.5rem);
  font-family: var(--sans);
  font-size: var(--step-body);
  line-height: 1.6;
  letter-spacing: 0.01em;
  color: var(--ink);
  background: var(--paper);
  overflow-x: clip;
}
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
.inline-block { display: inline-block; }

/* ── Pulsanti: blocchi pieni, angoli vivi ── */
.lp-btn {
  display: inline-flex; align-items: center; justify-content: center;
  min-height: 52px; padding: 0 26px;
  font-family: var(--sans); font-weight: 500; font-size: var(--step-body); letter-spacing: 0.01em;
  transition: background-color .2s, color .2s;
}
.lp :is(a, button, input):focus-visible { outline: 3px solid var(--charcoal); outline-offset: 3px; }
.lp-btn--pink { background: var(--pink); color: var(--carmine); }
.lp-btn--pink:hover { background: var(--paper); }
.lp-btn--carmine { background: var(--carmine); color: var(--paper); }
.lp-btn--carmine:hover { background: var(--carmine-deep); }

/* ── Intestazione ── */
.lp-bar {
  position: fixed; inset: 0 0 auto 0; z-index: 30;
  display: flex; align-items: center; gap: 32px;
  padding: 16px var(--margin);
  transition: color .3s, background-color .3s;
}
.lp-bar--carmine { color: var(--paper); background: var(--carmine); }
.lp-bar--paper { color: var(--carmine); background: var(--paper); }
.lp-word { font-family: var(--serif); font-size: 1.5rem; font-weight: 500; letter-spacing: -0.02em; margin-right: auto; }
.lp-links { display: flex; gap: 28px; }
.lp-links a:hover { text-decoration: underline; text-underline-offset: 4px; }
.lp-btn--bar { min-height: 44px; font-size: var(--step-small); }
.lp-bar--paper .lp-btn--bar { background: var(--carmine); color: var(--paper); }
.lp-bar--paper .lp-btn--bar:hover { background: var(--carmine-deep); }
.lp-bar--carmine .lp-btn--bar { background: var(--pink); color: var(--carmine); }
.lp-bar--carmine .lp-btn--bar:hover { background: var(--paper); }

/* ── Hero: carta sopra, carminio sotto, bordo netto a metà ── */
.hero {
  position: relative; min-height: 100svh;
  background: var(--paper);
  display: flex; flex-direction: column; justify-content: center;
  padding: 120px var(--margin) 56px;
  --seam: 50%;
}
.hero-field { position: absolute; inset: 50% 0 0 0; background: var(--carmine); }
.hero-title {
  position: relative; margin: 0 auto; width: 100%;
  font-family: var(--serif); font-weight: 500; font-size: clamp(56px, 9vw, 148px);
  line-height: 0.95; letter-spacing: -0.03em; text-align: center;
  height: 1.95em;
}
/* il titolo sta a cavallo della linea tra i due campi: la linea taglia il punto --seam del titolo */
.hero-title { position: absolute; left: 0; right: 0; top: calc(50% - 0.97em); padding: 0 var(--margin); }
.hero-layer { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; }
.hero-layer--top { color: var(--carmine); clip-path: inset(-20% 0 calc(100% - var(--seam)) 0); }
.hero-layer--bottom { color: var(--paper); clip-path: inset(var(--seam) 0 -30% 0); }
.hero-layer--top .slot { visibility: hidden; }
.slot.slot--phone { display: none; }
.hero-line { display: block; white-space: nowrap; }
.hero-line--slot { display: flex; align-items: center; justify-content: center; gap: 0.2em; }
.slot {
  display: inline-flex; flex-direction: column; justify-content: flex-end;
  width: 3.3em; height: 0.78em; padding: 0 0.14em 0.1em;
  background: var(--paper); color: var(--ink);
  font-family: var(--sans); text-align: left; letter-spacing: 0;
}
.hero-layer--bottom .slot { background: var(--pink); }
.slot-label { font-size: max(13px, 0.1em); line-height: 1.2; color: #5d5656; }
.slot-value { font-size: 0.24em; line-height: 1.25; border-bottom: 0.012em dashed var(--ink); white-space: nowrap; overflow: hidden; }
.caret { display: inline-block; width: 0.06em; height: 0.85em; margin-left: 0.04em; background: var(--carmine); vertical-align: -0.1em; animation: blink 1s steps(1) infinite; }
@keyframes blink { 50% { opacity: 0; } }
.hero-foot { position: relative; margin-top: auto; padding: 24px; background: var(--carmine); display: flex; flex-direction: column; align-items: center; gap: 22px; text-align: center; color: var(--paper); }
.hero-foot p { max-width: 42ch; font-size: var(--step-lead); line-height: 1.45; margin: 0; }
.hero-note { font-size: var(--step-small); color: var(--pink); }

/* ── Il problema ── */
.intro { background: var(--paper); padding: clamp(120px, 14vw, 200px) 0 clamp(120px, 12vw, 180px); }
.intro-grid { display: grid; grid-template-columns: repeat(12, 1fr); column-gap: 24px; padding: 0 var(--margin); }
.intro-text { grid-column: 1 / 7; position: relative; z-index: 2; padding-top: 8vh; }
.intro-title { font-family: var(--serif); font-weight: 500; font-size: var(--step-head); line-height: 1.02; letter-spacing: -0.03em; color: var(--carmine); margin: 0; }
.intro-lead { margin: 36px 0 0; max-width: 34ch; font-size: var(--step-lead); line-height: 1.45; }
.intro-small { margin: 64px 0 0; width: min(360px, 70%); }
.intro-small img { width: 100%; aspect-ratio: 9 / 10; object-fit: cover; display: block; }
.intro-small figcaption { margin-top: 12px; font-size: var(--step-body); }
.intro-photo { grid-column: 7 / 13; margin: 0; }
.intro-photo img { width: 100%; height: 100%; min-height: 70vh; object-fit: cover; object-position: 55% 50%; display: block; }

/* ── Come funziona ── */
.how { background: var(--charcoal); color: var(--paper); padding: clamp(120px, 12vw, 180px) var(--margin); }
.how-title { font-family: var(--serif); font-weight: 500; font-size: var(--step-head); line-height: 1; letter-spacing: -0.03em; margin: 0 0 64px; }
.how-bar { display: flex; gap: 6px; min-height: 26vh; }
.how-seg { display: flex; flex-direction: column; justify-content: space-between; gap: 24px; padding: 20px; min-width: 12%; flex-basis: 0; }
.how-seg--0 { background: var(--pink); color: var(--carmine); }
.how-seg--1 { background: var(--carmine); color: var(--paper); }
.how-seg--2 { background: var(--paper); color: var(--ink); }
.how-time { font-family: var(--serif); font-size: clamp(22px, 2.2vw, 34px); line-height: 1.05; }
.how-clause { font-size: var(--step-body); line-height: 1.4; max-width: 24ch; }
.how-after { margin: 48px 0 0; font-size: var(--step-lead); }

/* ── La cosa viva ── */
.show { background: var(--carmine); color: var(--paper); padding: clamp(120px, 12vw, 180px) var(--margin); }
.show-title { font-family: var(--serif); font-weight: 500; font-size: var(--step-head); line-height: 1; letter-spacing: -0.03em; margin: 0; }
.show-input { margin-top: 40px; }
.show-input input {
  width: 100%; border: 0; border-bottom: 3px dashed var(--muted-pink); border-radius: 0; padding: 0 0 8px; background: transparent; color: var(--pink);
  font-family: var(--serif); font-weight: 500; font-size: clamp(48px, 8vw, 132px); letter-spacing: -0.03em; line-height: 1.1;
  caret-color: var(--paper);
}
.show-input input:focus { outline: none; border-bottom-color: var(--paper); }
.show-answer { margin-top: 48px; display: grid; grid-template-columns: 7fr 5fr; gap: 24px; align-items: start; }
.paper { background: var(--paper); color: #111; padding: 32px 36px; font-family: Helvetica, Arial, sans-serif; font-size: 16px; line-height: 2.2; }
.paper-head { font-weight: 700; font-size: 18px; margin: 0 0 8px; letter-spacing: 0; }
.paper-row { margin: 0; }
.fill { display: inline-block; border-bottom: 1px solid #111; /* taste-skill-ignore: riga del modulo (frammento data-ui) */ color: var(--carmine); font-weight: 600; padding: 0 4px; line-height: 1.4; }
.fill--s { min-width: 6em; } .fill--m { min-width: 8em; } .fill--l { min-width: 11em; }
.archive { background: var(--carmine-deep); padding: 24px; font-size: 16px; }
.archive-head { font-weight: 500; margin: 0 0 12px; font-size: var(--step-body); }
.archive-row { display: grid; grid-template-columns: 1fr auto auto; gap: 16px; padding: 12px 14px; color: var(--pink); }
.archive-row + .archive-row { margin-top: 4px; }
.archive-row--new { background: var(--paper); color: var(--ink); }
.archive-dl { font-weight: 500; text-decoration: underline; text-underline-offset: 3px; }
.show-examples { margin-top: 40px; display: flex; flex-wrap: wrap; gap: 12px 28px; }
.show-examples button { font-size: var(--step-body); color: var(--pink); text-decoration: underline; text-underline-offset: 5px; transition: color .2s; }
.show-examples button:hover { color: var(--paper); }

/* ── Prezzo ── */
.price { background: var(--pink); color: var(--carmine); min-height: 80svh; padding: clamp(120px, 12vw, 180px) var(--margin); display: flex; flex-direction: column; justify-content: center; }
.price-sentence { font-family: var(--serif); font-weight: 500; font-size: clamp(48px, 8vw, 132px); line-height: 1; letter-spacing: -0.03em; margin: 0; }
.price-block { display: inline-block; background: var(--carmine); color: var(--paper); padding: 0 0.15em; }
.price-foot { margin-top: 64px; display: grid; grid-template-columns: 6fr 6fr; gap: 24px; align-items: end; }
.price-foot p { margin: 0; max-width: 44ch; color: var(--ink); }
.price-action { justify-self: end; }

/* ── Domande ── */
.faq { background: var(--paper); padding: clamp(120px, 12vw, 180px) var(--margin); }
.faq-row { display: grid; grid-template-columns: repeat(12, 1fr); column-gap: 24px; }
.faq-row + .faq-row { margin-top: 72px; }
.faq-q { grid-column: 1 / 7; margin: 0; font-family: var(--serif); font-weight: 500; font-size: clamp(26px, 2.6vw, 40px); line-height: 1.1; letter-spacing: -0.02em; color: var(--ink); }
.faq-q--soft { color: var(--carmine); }
.faq-a { grid-column: 8 / 13; margin: 0; max-width: 42ch; padding-top: 0.2em; }

/* ── Chiusura ── */
.cta { background: var(--paper-2); color: var(--carmine); min-height: 90svh; padding: clamp(120px, 12vw, 180px) var(--margin) 48px; display: flex; flex-direction: column; justify-content: space-between; }
.cta-title { font-family: var(--serif); font-weight: 500; font-size: clamp(48px, 9vw, 150px); line-height: 0.95; letter-spacing: -0.03em; margin: 0; }
.cta-foot { margin-top: 80px; display: grid; grid-template-columns: 5fr 7fr; gap: 24px; align-items: end; }
.cta-foot p { margin: 0; max-width: 38ch; color: var(--ink); font-size: var(--step-lead); line-height: 1.45; }
.cta-action { justify-self: end; }

/* ── Footer ── */
.foot { position: relative; background: var(--carmine); color: var(--paper); padding: clamp(100px, 10vw, 160px) var(--margin) 0; overflow: hidden; }
.foot-top { display: grid; grid-template-columns: 7fr 2fr 3fr; gap: 24px; }
.foot-title { font-family: var(--serif); font-weight: 500; font-size: clamp(40px, 5vw, 80px); line-height: 1; letter-spacing: -0.03em; margin: 0 0 32px; }
.foot-links { grid-column: 3; list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
.foot-links a:hover, .foot-credits a:hover { text-decoration: underline; text-underline-offset: 4px; }
.foot-legal { margin-top: 96px; display: flex; flex-wrap: wrap; gap: 16px 40px; justify-content: space-between; align-items: center; font-size: var(--step-small); }
.foot-credits { margin: 0; max-width: 70ch; color: var(--paper); }
.foot-credits a { text-decoration: underline; text-underline-offset: 3px; }
.foot-word { margin: 0; font-family: var(--serif); font-weight: 500; font-size: 30vw; line-height: 0.8; letter-spacing: -0.04em; white-space: nowrap; color: var(--pink); transform: translateY(22%); }

/* ── Chat (galleggia: unica ombra della pagina) ── */
.lp-chat { position: fixed; right: 20px; bottom: 20px; z-index: 40; background: var(--charcoal); color: var(--paper); padding: 14px 20px; font-weight: 500; font-size: var(--step-small); transition: background-color .2s, opacity .3s; }
.lp-chat--away { opacity: 0; visibility: hidden; transition: opacity .3s, visibility 0s .3s; }
.lp-chat:hover { background: #000; }

/* ── Tablet ── */
@media (max-width: 1023px) {
  .intro-grid { grid-template-columns: 1fr; }
  .intro-text, .intro-photo { grid-column: 1; }
  .intro-text { padding-top: 0; }
  .intro-photo { margin-top: 48px; }
  .intro-photo img { min-height: 0; aspect-ratio: 3 / 2; }
  .intro-small { display: none; }
}

/* ── Telefono ── */
@media (max-width: 767px) {
  .lp-links { display: none; }
  .lp-word { font-size: 1.15rem; }
  .lp-bar { gap: 12px; padding-block: 12px; }
  .lp-btn--bar { padding: 0 14px; }
  .hero { padding-top: 96px; }
  .hero-field { top: 46%; }
  .hero-title { font-size: 13vw; height: 1.95em; top: calc(46% - 0.97em); }
  .hero-title .slot { display: none; }
  .slot.slot--phone { display: inline-flex; position: absolute; left: 50%; top: calc(46% + 15vw); transform: translateX(-50%); width: 72vw; height: auto; padding: 10px 14px; font-size: 92px; background: var(--pink); }
  .hero-foot p { font-size: 16px; max-width: 28ch; }
  .intro-grid { grid-template-columns: 1fr; }
  .intro-text, .intro-photo { grid-column: 1; }
  .intro-text { padding-top: 0; }
  .intro-title { font-size: 10vw; }
  .intro-lead { font-size: 17px; }
  .intro-photo { margin-top: 40px; order: 2; }
  .intro-photo img { min-height: 0; aspect-ratio: 4 / 5; }
  .how-bar { flex-direction: column; min-height: 70svh; }
  .how-seg { min-height: 72px; }
  .how-title, .show-title { font-size: 11vw; }
  .show-input input { font-size: 11vw; }
  .show-answer { grid-template-columns: 1fr; }
  .paper { padding: 20px; font-size: 14px; line-height: 2.1; }
  .fill--l { min-width: 8em; }
  .show-examples button { min-height: 44px; padding: 0 14px; background: var(--carmine-deep); text-decoration: none; }
  .price-sentence { font-size: 11vw; }
  .price-foot, .cta-foot { grid-template-columns: 1fr; }
  .price-action, .cta-action { justify-self: stretch; }
  .price-action .lp-btn, .cta-action .lp-btn { width: 100%; }
  .faq-row { grid-template-columns: 1fr; }
  .faq-row + .faq-row { margin-top: 56px; }
  .faq-q, .faq-a { grid-column: 1; }
  .faq-q { font-size: 24px; }
  .faq-a { margin-top: 12px; }
  .cta { min-height: 80svh; }
  .cta-title { font-size: 12vw; }
  .foot-top { grid-template-columns: 1fr; }
  .foot-links { grid-column: 1; flex-direction: row; flex-wrap: wrap; gap: 8px 20px; }
  .foot-word { font-size: 34vw; }
  .lp-chat { right: 12px; bottom: 12px; }
}
</style>
