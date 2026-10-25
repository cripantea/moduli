<script setup lang="ts">
import LaunchBanner from '@/Components/LaunchBanner.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import InputError from '@/Components/InputError.vue'
import { computed, nextTick, ref } from 'vue'
import { setConsent } from '@/tracking'

// Registrazione in 4 passi brevi: due domande a scelta rapida, email, nome e cellulare, password.
// L'azienda non si chiede: la ragione sociale arriva al pagamento.
const settori = ['Agenzia assicurativa o broker', 'Studio professionale', 'Agenzia immobiliare', 'CAF o patronato', 'Altro']
const volumi = ['Meno di 10', 'Da 10 a 50', 'Più di 50']
const form = useForm({
  settore: '',
  volume: '',
  name: '',
  email: '',
  phone: '',
  password: '',
  misurazione: false,
})

const step = ref(1)
const showPassword = ref(false)
const formEl = ref<HTMLFormElement | null>(null)
const STEPS = 4
const titles = ['Due domande veloci', 'La tua email', 'Come ti chiami?', 'Scegli una password']
const subtitles = ['Così il tecnico arriva preparato sui tuoi moduli.', 'Ci serve per farti accedere e mandarti le fatture.', 'Il cellulare serve per fissare i 30 minuti col tecnico.', 'Ultimo passo: almeno 8 caratteri.']
const stepFields: Record<number, string[]> = { 1: ['settore', 'volume'], 2: ['email'], 3: ['name', 'phone'], 4: ['password'] }
const title = computed(() => titles[step.value - 1])
const subtitle = computed(() => subtitles[step.value - 1])

function focusFirst() {
  nextTick(() => formEl.value?.querySelector<HTMLInputElement>('input:not([type=hidden])')?.focus())
}

// Al primo passo si tocca la risposta: quando ci sono tutte e due si va avanti da soli.
function pick(field: 'settore' | 'volume', value: string) {
  form[field] = value
  if (form.settore && form.volume) {
    step.value = 2
    focusFirst()
  }
}

function stepValid(): boolean {
  if (step.value === 1 && (!form.settore || !form.volume)) return false
  const inputs = Array.from(formEl.value?.querySelectorAll<HTMLInputElement>('input') ?? [])
  const invalid = inputs.find((input) => !input.checkValidity())
  invalid?.reportValidity()
  return !invalid
}

function back() {
  step.value = Math.max(1, step.value - 1)
  focusFirst()
}

const submit = () => {
  if (!stepValid()) return
  if (step.value < STEPS) {
    step.value++
    focusFirst()
    return
  }
  // Consenso esplicito e facoltativo: vale come "Accetta" del banner (pixel, Clarity, Conversions API).
  if (form.misurazione) setConsent('si')
  form.post(route('register'), {
    onError: (errors) => {
      // Torna al primo passo che ha un errore.
      const first = [1, 2, 3, 4].find((n) => stepFields[n].some((field) => field in errors))
      if (first) step.value = first
      focusFirst()
    },
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head title="Crea account — Fusion Moduli" />

  <div class="min-h-screen bg-gray-50 flex flex-col">
    <LaunchBanner />

    <!-- Top nav -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-white">
      <Link href="/" class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-lg bg-blue-700 flex items-center justify-center">
          <svg class="w-4 h-4" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g transform="translate(2.2, 2.7) scale(0.089)">
              <path fill="white" d="M 82 7 C 97 0 114 17 111 43 C 108 68 88 80 60 82 C 73 65 75 48 62 36 C 50 24 30 22 25 11 C 18 -2 32 -8 48 0 C 60 6 74 7 82 7 Z"/>
              <path fill="white" d="M 45 84 C 61 75 78 90 74 114 C 70 136 50 150 27 150 C 38 134 40 117 28 105 C 17 93 1 93 -2 79 C -6 63 8 52 23 57 C 34 61 43 77 45 84 Z"/>
            </g>
          </svg>
        </div>
        <span class="text-sm font-bold text-gray-900">Fusion Moduli</span>
      </Link>
      <Link href="/login" class="text-sm text-gray-600 font-medium hover:text-gray-900">
        Hai già un account? <span class="text-indigo-600">Accedi</span>
      </Link>
    </div>

    <!-- Register form -->
    <div class="flex-1 flex items-center justify-center px-4 py-12">
      <div class="w-full max-w-sm">

        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-gray-900">Crea il tuo account</h1>
          <p class="text-sm text-gray-500 mt-1.5">Carica il tuo primo modulo in 2 minuti</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
          <div class="mb-6">
            <div class="flex items-center justify-between text-xs text-gray-500">
              <span>Passo {{ step }} di {{ STEPS }}</span>
              <button v-if="step > 1" type="button" class="text-indigo-600 hover:underline" @click="back">Indietro</button>
            </div>
            <div class="mt-2 h-1.5 rounded-full bg-gray-100">
              <div class="h-1.5 rounded-full bg-indigo-600 transition-all" :style="{ width: `${(step / STEPS) * 100}%` }" />
            </div>
            <h2 class="mt-5 text-lg font-bold text-gray-900">{{ title }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
          </div>

          <form ref="formEl" @submit.prevent="submit" class="space-y-5" novalidate>

            <template v-if="step === 1">
              <fieldset>
                <legend class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Che lavoro fai?</legend>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="o in settori" :key="o" type="button"
                    class="px-3 py-2 rounded-lg border text-sm transition"
                    :class="form.settore === o ? 'border-indigo-600 bg-indigo-50 text-indigo-700 font-semibold' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                    @click="pick('settore', o)"
                  >{{ o }}</button>
                </div>
              </fieldset>
              <fieldset>
                <legend class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-2">Quanti moduli compili a settimana?</legend>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="o in volumi" :key="o" type="button"
                    class="px-3 py-2 rounded-lg border text-sm transition"
                    :class="form.volume === o ? 'border-indigo-600 bg-indigo-50 text-indigo-700 font-semibold' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                    @click="pick('volume', o)"
                  >{{ o }}</button>
                </div>
              </fieldset>
            </template>

            <div v-if="step === 2">
              <label for="email" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Email</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="username"
                inputmode="email"
                required
                autofocus
                placeholder="nome@azienda.it"
                class="w-full border rounded-lg px-3 py-3 text-base outline-none focus:ring-2 transition"
                :class="form.errors.email ? 'border-red-400 focus:ring-red-300' : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'"
              />
              <InputError :message="form.errors.email" class="mt-1.5" />
            </div>

            <template v-if="step === 3">
              <div>
                <label for="name" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Nome e cognome</label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  autocomplete="name"
                  required
                  placeholder="Mario Rossi"
                  class="w-full border rounded-lg px-3 py-3 text-base outline-none focus:ring-2 transition"
                  :class="form.errors.name ? 'border-red-400 focus:ring-red-300' : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'"
                />
                <InputError :message="form.errors.name" class="mt-1.5" />
              </div>
              <div>
                <label for="phone" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Cellulare</label>
                <input
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  autocomplete="tel"
                  inputmode="tel"
                  required
                  minlength="6"
                  maxlength="30"
                  placeholder="333 123 4567"
                  class="w-full border rounded-lg px-3 py-3 text-base outline-none focus:ring-2 transition"
                  :class="form.errors.phone ? 'border-red-400 focus:ring-red-300' : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'"
                />
                <InputError :message="form.errors.phone" class="mt-1.5" />
              </div>
            </template>

            <div v-if="step === 4">
              <label for="password" class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Password</label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="new-password"
                  required
                  minlength="8"
                  placeholder="Minimo 8 caratteri"
                  class="w-full border rounded-lg pl-3 pr-20 py-3 text-base outline-none focus:ring-2 transition"
                  :class="form.errors.password ? 'border-red-400 focus:ring-red-300' : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500'"
                />
                <button type="button" class="absolute inset-y-0 right-0 px-3 text-xs font-semibold text-gray-500 hover:text-gray-800" @click="showPassword = !showPassword">
                  {{ showPassword ? 'Nascondi' : 'Mostra' }}
                </button>
              </div>
              <InputError :message="form.errors.password" class="mt-1.5" />
              <label class="mt-4 flex items-start gap-2.5 text-sm text-gray-600">
                <input v-model="form.misurazione" type="checkbox" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                <span>Accetto i cookie di misurazione di Meta e Microsoft Clarity, per migliorare annunci e sito. <span class="text-gray-400">(Facoltativo)</span></span>
              </label>
            </div>

            <button
              type="submit"
              :disabled="form.processing"
              class="w-full flex items-center justify-center gap-2 py-3 px-4 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 disabled:cursor-not-allowed rounded-lg text-white text-base font-semibold transition"
            >
              <svg v-if="form.processing" class="animate-spin h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              {{ form.processing ? 'Creazione account…' : step < STEPS ? 'Continua' : 'Crea il tuo account' }}
            </button>

          </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-5 leading-relaxed px-2">
          Creando un account accetti i nostri
          <a href="/termini" target="_blank" class="underline hover:text-gray-600">Termini di servizio</a>
          e la
          <a href="https://fusionsoft.it/privacy" target="_blank" rel="noopener" class="underline hover:text-gray-600">Privacy Policy</a>.
          Ti scriveremo via email e WhatsApp solo per aiutarti a iniziare; puoi chiederci di smettere quando vuoi.
        </p>

        <p class="text-center text-sm text-gray-500 mt-4">
          Hai già un account?
          <Link href="/login" class="text-indigo-600 font-semibold hover:underline">Accedi</Link>
        </p>

      </div>
    </div>
  </div>
</template>
