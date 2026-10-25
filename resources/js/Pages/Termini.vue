<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'

defineProps<{
  azienda: { name: string; vat: string; address: string | null; email: string | null }
}>()

const aggiornati = '25 ottobre 2026'
</script>

<template>
  <Head>
    <title>Termini di servizio — Fusion Moduli</title>
    <meta name="robots" content="noindex">
  </Head>

  <div class="min-h-screen bg-white">
    <header class="border-b border-gray-100">
      <div class="max-w-3xl mx-auto px-4 h-14 flex items-center justify-between">
        <Link href="/" class="text-sm font-bold text-gray-900">Fusion Moduli</Link>
        <Link href="/register" class="text-sm font-semibold text-indigo-600">Carica il tuo modulo →</Link>
      </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 py-12 text-[15px] leading-relaxed text-gray-700">
      <h1 class="text-3xl font-bold text-gray-900">Termini di servizio</h1>
      <p class="mt-2 text-sm text-gray-500">Ultimo aggiornamento: {{ aggiornati }}</p>

      <section class="mt-10 space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">1. Chi siamo</h2>
        <p>
          Fusion Moduli (moduli.fusionsoft.it) è un servizio di {{ azienda.name }}, P.IVA {{ azienda.vat }}<template v-if="azienda.address">, con sede in {{ azienda.address }}</template>
          (di seguito "Fusionsoft" o "noi"). Per qualsiasi comunicazione puoi usare il pulsante <strong>Assistenza</strong> dentro l'app<template v-if="azienda.email"> o scrivere a {{ azienda.email }}</template>.
        </p>
      </section>

      <section class="mt-8 space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">2. Il servizio</h2>
        <p>
          Fusion Moduli ti permette di caricare moduli in formato PDF, farne riconoscere i campi da un sistema di intelligenza artificiale,
          compilarli e scaricare il documento compilato, conservando l'archivio delle compilazioni.
        </p>
        <p>
          Il riconoscimento automatico dei campi e l'assistente in chat sono strumenti di aiuto e possono contenere errori:
          prima di usare un documento compilato verificane sempre il contenuto. Resti responsabile dei documenti che produci e usi.
        </p>
      </section>

      <section class="mt-8 space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">3. Account</h2>
        <p>
          Per usare il servizio crei un account per la tua azienda o attività. Devi fornire dati veri e custodire le credenziali:
          sei responsabile di quello che avviene con il tuo account. Il servizio è pensato per uso professionale.
        </p>
      </section>

      <section class="mt-8 space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">4. Prova, prezzo e pagamento</h2>
        <p>
          Senza abbonamento puoi caricare e compilare alcuni moduli per provare il servizio, entro i limiti indicati nell'app;
          per scaricare i documenti compilati serve l'abbonamento.
        </p>
        <p>
          L'abbonamento costa <strong>49 € al mese IVA inclusa</strong>. Fino al 29 ottobre 2026 alle 23:59 (ora italiana) vale il
          <strong>prezzo di lancio di 29 € al mese</strong>: chi si abbona entro quella data paga 29 € al mese per tutto il tempo in cui
          il suo abbonamento resta attivo. Si paga in anticipo con carta tramite Stripe e si rinnova automaticamente ogni mese finché
          non lo disdici. Eventuali altre variazioni di prezzo ti saranno comunicate con almeno 30 giorni di anticipo e varranno dal
          rinnovo successivo: se non le accetti puoi disdire prima.
        </p>
        <p>
          Per ricevere la fattura elettronica inserisci al pagamento i tuoi dati di fatturazione (ragione sociale, partita IVA,
          codice destinatario o PEC).
        </p>
      </section>

      <section class="mt-8 space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">5. Soddisfatto o rimborsato</h2>
        <p>
          Se entro <strong>30 giorni</strong> dal primo pagamento il servizio non fa per te, chiedi il rimborso con il pulsante
          <strong>Assistenza</strong>: ti restituiamo l'intero importo pagato, senza dover dare spiegazioni, e l'abbonamento viene chiuso.
          La garanzia vale una volta per azienda.
        </p>
      </section>

      <section class="mt-8 space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">6. Disdetta</h2>
        <p>
          Puoi disdire in qualsiasi momento, con un clic, dal pulsante <strong>Abbonamento</strong> nell'app. L'abbonamento resta attivo
          fino alla fine del mese già pagato e non viene più rinnovato; non ci sono penali. Dopo la scadenza puoi ancora accedere
          al tuo account, ma non scaricare nuovi documenti.
        </p>
        <p>
          Se agisci come consumatore hai comunque il diritto di recesso entro 14 giorni previsto dal Codice del Consumo;
          la garanzia di 30 giorni lo comprende ed è più ampia.
        </p>
      </section>

      <section class="mt-8 space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">7. Uso corretto</h2>
        <p>
          Non puoi usare il servizio per attività illecite, per caricare contenuti di cui non hai diritto, per tentare di accedere
          a dati di altri clienti, per sovraccaricare il sistema o per aggirarne i limiti. In questi casi possiamo sospendere l'account.
        </p>
      </section>

      <section class="mt-8 space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">8. I tuoi dati e i tuoi documenti</h2>
        <p>
          I moduli e i documenti che carichi restano tuoi e sono visibili solo agli utenti della tua azienda. Li usiamo
          esclusivamente per fornirti il servizio: per il riconoscimento dei campi e per l'assistente vengono elaborati da fornitori
          di intelligenza artificiale, senza essere usati per altri scopi da parte nostra.
          Le conversazioni con l'assistente vengono conservate per migliorare l'assistenza.
        </p>
        <p>
          Per i dati personali contenuti nei documenti tu sei il titolare del trattamento e noi agiamo come responsabile, secondo
          l'<a href="https://fusionsoft.it/privacy" target="_blank" rel="noopener" class="text-indigo-600 underline">informativa privacy</a>.
          Se chiudi l'account puoi chiederci di cancellare i tuoi dati.
        </p>
      </section>

      <section class="mt-8 space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">9. Disponibilità e responsabilità</h2>
        <p>
          Facciamo il possibile perché il servizio sia sempre disponibile e i dati siano al sicuro, ma possono esserci interruzioni
          per manutenzione o cause non dipendenti da noi. Nei limiti consentiti dalla legge, la nostra responsabilità verso un cliente
          professionale è limitata all'importo pagato negli ultimi 12 mesi e non comprende danni indiretti o mancati guadagni.
          Restano salvi i diritti che la legge riconosce ai consumatori.
        </p>
      </section>

      <section class="mt-8 space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">10. Modifiche e legge applicabile</h2>
        <p>
          Possiamo aggiornare questi termini: le modifiche importanti ti saranno comunicate in anticipo. Si applica la legge italiana.
          Per i clienti professionali è competente il foro della sede di Fusionsoft; per i consumatori quello del loro luogo di residenza.
        </p>
      </section>
    </main>
  </div>
</template>
