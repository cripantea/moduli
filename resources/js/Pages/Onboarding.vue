<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import FieldCoordEditor from '@/Components/Templates/FieldCoordEditor.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import axios from 'axios'

interface Field {
  _uid: number
  name: string
  label: string
  type: string
  required: boolean
  page?: number
  x?: number
  y?: number
  w?: number
  lines?: number
  line_gap?: number
  comb?: number
}

// Su telefono si può caricare il PDF, ma sistemare le posizioni dei campi richiede il computer.
const smallScreen = typeof window !== 'undefined' && window.matchMedia('(max-width: 767px)').matches

type Step = 'upload' | 'reading' | 'ready'

const step = ref<Step>('upload')
const error = ref<string | null>(null)
const dragging = ref(false)

const form = useForm({
  name: '',
  pdf_template_s3_key: null as string | null,
  fields_schema: [] as Field[],
  font_size: 10,
  onboarding: true,
})

async function handleFile(file: File | undefined) {
  if (!file) return
  if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
    error.value = 'Carica un file PDF.'
    return
  }

  error.value = null
  step.value = 'reading'
  form.name = file.name.replace(/\.pdf$/i, '').replace(/[_-]+/g, ' ').trim().slice(0, 120) || 'Il mio modulo'

  try {
    const fd = new FormData()
    fd.append('pdf', file)
    const up = await axios.post<{ s3_key: string }>(route('templates.upload-pdf'), fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    form.pdf_template_s3_key = up.data.s3_key

    const ex = await axios.post<{ fields: Omit<Field, '_uid'>[] }>(route('templates.extract-fields'), {
      s3_key: up.data.s3_key,
    })
    form.fields_schema = ex.data.fields.map((f, i) => ({ ...f, _uid: i + 1 }))
    step.value = 'ready'
  } catch (err) {
    const data = axios.isAxiosError(err) ? err.response?.data : null
    error.value = data?.error ?? data?.message ?? 'Non siamo riusciti a leggere il PDF. Riprova con un altro file.'
    step.value = 'upload'
  }
}

function onInput(e: Event) {
  const input = e.target as HTMLInputElement
  handleFile(input.files?.[0])
  input.value = ''
}

function onDrop(e: DragEvent) {
  dragging.value = false
  handleFile(e.dataTransfer?.files?.[0])
}

function removeField(uid: number) {
  form.fields_schema = form.fields_schema.filter(f => f._uid !== uid)
}

function startCompiling() {
  form.post(route('templates.store'))
}
</script>

<template>
  <Head title="Benvenuto" />

  <AuthenticatedLayout>
    <template #header>
      <h1 class="text-base font-semibold text-gray-900">Il tuo primo modulo</h1>
    </template>

    <div class="mx-auto py-10 px-4" :class="step === 'ready' ? 'max-w-6xl' : 'max-w-2xl'">
      <!-- Passi -->
      <ol class="flex items-center gap-2 text-xs font-medium text-gray-400 mb-8">
        <li :class="step === 'upload' ? 'text-indigo-600' : 'text-gray-900'">1. Carica il PDF</li>
        <li>›</li>
        <li :class="step === 'reading' ? 'text-indigo-600' : step === 'ready' ? 'text-gray-900' : ''">2. L'AI trova i campi</li>
        <li>›</li>
        <li>3. Compila e scarica</li>
      </ol>

      <!-- 1. Upload -->
      <div v-if="step === 'upload'">
        <h2 class="text-2xl font-bold text-gray-900">Carica un modulo che compili spesso</h2>
        <p class="mt-2 text-sm text-gray-600">
          Quello che oggi compili con Adobe o col browser, magari con il cliente davanti che aspetta.
          L'AI riconosce da sola i campi: da qui in poi lo compili in pochi secondi, sempre uguale.
        </p>

        <p v-if="smallScreen" class="mt-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900">
          Moduli si usa meglio dal computer: per controllare i campi sul PDF serve uno schermo grande.
          Il tuo account è già pronto, accedi da PC su <span class="font-semibold">moduli.fusionsoft.it</span>.
        </p>

        <label
          class="mt-6 flex flex-col items-center justify-center gap-3 border-2 border-dashed rounded-2xl px-6 py-14 cursor-pointer transition"
          :class="dragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 bg-white hover:border-indigo-400'"
          @dragover.prevent="dragging = true"
          @dragleave.prevent="dragging = false"
          @drop.prevent="onDrop"
        >
          <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 16V4m0 0l-4 4m4-4l4 4M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
          </svg>
          <span class="text-sm font-semibold text-gray-900">Trascina qui il PDF o clicca per sceglierlo</span>
          <span class="text-xs text-gray-500">Massimo 20 MB</span>
          <input type="file" accept="application/pdf,.pdf" class="hidden" @change="onInput" />
        </label>

        <p v-if="error" class="mt-4 text-sm text-red-600">{{ error }}</p>
      </div>

      <!-- 2. Lettura AI -->
      <div v-else-if="step === 'reading'" class="text-center py-16">
        <div class="mx-auto w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin" />
        <h2 class="mt-6 text-xl font-bold text-gray-900">L'AI sta leggendo "{{ form.name }}"…</h2>
        <p class="mt-2 text-sm text-gray-600">Cerca i campi da compilare. Di solito ci vogliono 20–40 secondi.</p>
      </div>

      <!-- 3. Campi trovati: il cliente controlla dove scriverà e corregge prima di compilare -->
      <div v-else>
        <h2 class="text-2xl font-bold text-gray-900">
          Fatto: {{ form.fields_schema.length }} {{ form.fields_schema.length === 1 ? 'campo trovato' : 'campi trovati' }}
        </h2>
        <p class="mt-2 text-sm text-gray-600">
          Controlla dove scriverà Moduli: ogni linea blu è un campo. Se una è fuori posto trascinala sopra la riga giusta;
          puoi anche rinominare o togliere i campi. Potrai sempre modificarli da "Template".
        </p>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_18rem] items-start">
          <div class="bg-white border border-gray-200 rounded-2xl p-4">
            <FieldCoordEditor
              :fields="form.fields_schema"
              :s3-key="form.pdf_template_s3_key"
              :font-size-pt="form.font_size"
              @update:fields="form.fields_schema = $event"
            />
          </div>

          <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
            <p class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-gray-500 border-b border-gray-100">Campi</p>
            <ul class="divide-y divide-gray-100 max-h-[32rem] overflow-y-auto">
              <li v-for="f in form.fields_schema" :key="f._uid" class="flex items-center gap-2 px-3 py-2">
                <input
                  v-model="f.label"
                  type="text"
                  class="flex-1 min-w-0 rounded-md border-gray-200 text-sm py-1 focus:border-indigo-500 focus:ring-indigo-500"
                  :aria-label="`Nome del campo ${f.label}`"
                />
                <button type="button" class="text-gray-400 hover:text-red-600 text-lg leading-none" title="Togli campo" @click="removeField(f._uid)">×</button>
              </li>
            </ul>
          </div>
        </div>

        <button
          type="button"
          :disabled="form.processing"
          class="mt-6 w-full sm:w-auto px-6 py-3 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50 transition"
          @click="startCompiling"
        >
          Compila il tuo modulo →
        </button>
        <p v-if="Object.keys(form.errors).length" class="mt-3 text-sm text-red-600">
          {{ Object.values(form.errors)[0] }}
        </p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
