<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import FieldCoordEditor from '@/Components/Templates/FieldCoordEditor.vue'
import { useForm, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import axios from 'axios'

interface FieldSchema {
  _uid: number
  name: string
  label: string
  type: string
  required: boolean
  page?: number
  x?: number
  y?: number
  w?: number
}

interface Template {
  id: number
  name: string
  pdf_template_s3_key: string | null
  fields_schema: FieldSchema[] | null
  font_size: number
}

const props = defineProps<{ template: Template | null }>()

const isNew = computed(() => !props.template)

const form = useForm({
  name:                props.template?.name ?? '',
  pdf_template_s3_key: props.template?.pdf_template_s3_key ?? null as string | null,
  fields_schema:       (props.template?.fields_schema ?? []) as FieldSchema[],
  font_size:           props.template?.font_size ?? 10,
})

// ── PDF Upload ──────────────────────────────────────────────────────────────
const uploading = ref(false)
const uploadError = ref<string | null>(null)

async function onPdfChange(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files?.length) return
  const file = input.files[0]
  uploadError.value = null
  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('pdf', file)
    const resp = await axios.post<{ s3_key: string }>(route('templates.upload-pdf'), fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    form.pdf_template_s3_key = resp.data.s3_key
  } catch {
    uploadError.value = 'Errore durante il caricamento del PDF.'
  } finally {
    uploading.value = false
    input.value = ''
  }
}

// ── AI Field Extract ────────────────────────────────────────────────────────
const extracting = ref(false)
const extractError = ref<string | null>(null)

async function extractFields() {
  if (!form.pdf_template_s3_key) return
  extracting.value = true
  extractError.value = null
  try {
    const resp = await axios.post<{ fields: Omit<FieldSchema, '_uid'>[] }>(
      route('templates.extract-fields'),
      { s3_key: form.pdf_template_s3_key }
    )
    let uid = Date.now()
    form.fields_schema = resp.data.fields.map(f => ({ ...f, _uid: uid++ }))
  } catch {
    extractError.value = 'Impossibile estrarre i campi con AI.'
  } finally {
    extracting.value = false
  }
}

// ── Manual field management ─────────────────────────────────────────────────
const FIELD_TYPES = ['text', 'textarea', 'date', 'number']

function addField() {
  form.fields_schema = [
    ...form.fields_schema,
    {
      _uid: Date.now(),
      name: `campo_${form.fields_schema.length + 1}`,
      label: `Campo ${form.fields_schema.length + 1}`,
      type: 'text',
      required: false,
      page: 1, x: 10, y: 10, w: 30,
    },
  ]
}

function removeField(uid: number) {
  form.fields_schema = form.fields_schema.filter(f => f._uid !== uid)
}

// ── Submit ──────────────────────────────────────────────────────────────────
function submit() {
  if (isNew.value) {
    form.post(route('templates.store'))
  } else {
    form.put(route('templates.update', props.template!.id))
  }
}

function destroy() {
  if (!confirm(`Eliminare il template "${props.template!.name}"?`)) return
  router.delete(route('templates.destroy', props.template!.id))
}
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between w-full">
        <h1 class="text-base font-semibold text-gray-900">
          {{ isNew ? 'Nuovo template' : 'Modifica template' }}
        </h1>
        <div class="flex items-center gap-2">
          <button v-if="!isNew" @click="destroy" type="button" class="px-3 py-1.5 text-xs text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition">
            Elimina
          </button>
          <a :href="route('templates.index')" class="px-3 py-1.5 text-xs text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Indietro</a>
        </div>
      </div>
    </template>

    <form @submit.prevent="submit" class="max-w-5xl space-y-6">

      <!-- Base info -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <h2 class="text-sm font-semibold text-gray-900">Informazioni</h2>
        <div class="grid grid-cols-3 gap-4">
          <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-700 mb-1">Nome template <span class="text-red-400">*</span></label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
              placeholder="es. Verbale di ispezione"
            />
            <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Dimensione font</label>
            <input
              v-model.number="form.font_size"
              type="number"
              min="6" max="24"
              class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
            />
          </div>
        </div>
      </div>

      <!-- PDF upload -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
        <h2 class="text-sm font-semibold text-gray-900">PDF base</h2>
        <div class="flex items-center gap-4">
          <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <span v-if="uploading">Caricamento...</span>
            <span v-else>{{ form.pdf_template_s3_key ? 'Sostituisci PDF' : 'Carica PDF' }}</span>
            <input type="file" accept=".pdf" @change="onPdfChange" class="hidden"/>
          </label>
          <span v-if="form.pdf_template_s3_key" class="text-xs text-green-600 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            PDF caricato
          </span>
        </div>
        <p v-if="uploadError" class="text-xs text-red-600">{{ uploadError }}</p>
      </div>

      <!-- Fields schema -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-semibold text-gray-900">Campi ({{ form.fields_schema.length }})</h2>
          <div class="flex gap-2">
            <button
              v-if="form.pdf_template_s3_key"
              type="button"
              @click="extractFields"
              :disabled="extracting"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-indigo-700 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition disabled:opacity-50"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
              {{ extracting ? 'Estrazione...' : 'Estrai con AI' }}
            </button>
            <button type="button" @click="addField" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
              + Aggiungi campo
            </button>
          </div>
        </div>
        <p v-if="extractError" class="text-xs text-red-600">{{ extractError }}</p>

        <!-- Field rows -->
        <div v-if="form.fields_schema.length" class="space-y-2">
          <div
            v-for="field in form.fields_schema"
            :key="field._uid"
            class="grid grid-cols-12 gap-2 items-start p-3 border border-gray-100 rounded-lg bg-gray-50"
          >
            <div class="col-span-3">
              <label class="text-[10px] font-medium text-gray-500 mb-0.5 block">Nome variabile</label>
              <input v-model="field.name" type="text" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="campo_1"/>
            </div>
            <div class="col-span-4">
              <label class="text-[10px] font-medium text-gray-500 mb-0.5 block">Etichetta</label>
              <input v-model="field.label" type="text" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="Etichetta visibile"/>
            </div>
            <div class="col-span-2">
              <label class="text-[10px] font-medium text-gray-500 mb-0.5 block">Tipo</label>
              <select v-model="field.type" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md bg-white focus:ring-1 focus:ring-indigo-500 outline-none">
                <option v-for="t in FIELD_TYPES" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
            <div class="col-span-2 pt-5 flex items-center gap-2">
              <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                <input type="checkbox" v-model="field.required" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"/>
                Obbligatorio
              </label>
            </div>
            <div class="col-span-1 pt-5 flex justify-end">
              <button type="button" @click="removeField(field._uid)" class="text-red-400 hover:text-red-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>
          </div>
        </div>
        <p v-else class="text-xs text-gray-400 italic">Nessun campo definito. Aggiungine uno manualmente o usa l'estrazione AI.</p>
      </div>

      <!-- Coord editor -->
      <div v-if="form.pdf_template_s3_key && form.fields_schema.length" class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
        <h2 class="text-sm font-semibold text-gray-900">Posizionamento campi sul PDF</h2>
        <FieldCoordEditor
          :fields="form.fields_schema"
          :s3-key="form.pdf_template_s3_key"
          @update:fields="form.fields_schema = $event"
        />
      </div>

      <!-- Submit -->
      <div class="flex items-center gap-3">
        <button
          type="submit"
          :disabled="form.processing"
          class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition"
        >
          {{ form.processing ? 'Salvataggio...' : (isNew ? 'Crea template' : 'Salva modifiche') }}
        </button>
        <a :href="route('templates.index')" class="px-5 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Annulla</a>
      </div>
    </form>
  </AuthenticatedLayout>
</template>
