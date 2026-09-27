<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import axios from 'axios'

interface FieldSchema {
  name: string
  label: string
  type: string
  required: boolean
  w?: number
  x?: number
  y?: number
  page?: number
}

interface Template {
  id: number
  name: string
  fields_schema: FieldSchema[] | null
  font_size: number
  pdf_template_s3_key: string | null
}

const props = defineProps<{
  templates: Template[]
  template?: number
}>()

const selectedId = ref<number | null>(props.template ? Number(props.template) : null)

const selectedTemplate = computed<Template | null>(() =>
  selectedId.value ? (props.templates.find(t => t.id === selectedId.value) ?? null) : null
)

const form = useForm<{ module_template_id: number | null; values: Record<string, string> }>({
  module_template_id: selectedId.value,
  values: {},
})

watch(selectedId, (id) => {
  form.module_template_id = id
  form.values = {}
})

function submit() {
  form.post(route('compiled.store'))
}

function maxCharsForField(field: FieldSchema): number {
  if (!field.w) return 0
  const fontPt = selectedTemplate.value?.font_size ?? 10
  const fieldWidthMm = (field.w / 100) * 210
  const avgCharWidthMm = fontPt * 0.556 * (25.4 / 72)
  return Math.floor(fieldWidthMm / avgCharWidthMm)
}

function charCount(field: FieldSchema): number {
  return String(form.values[field.name] ?? '').length
}

// ── Live preview ──────────────────────────────────────────────────────────────

const previewContainerRef = ref<HTMLDivElement | null>(null)
const previewDataUrl      = ref<string | null>(null)
const previewLoading      = ref(false)
const previewError        = ref<string | null>(null)
const previewPage         = ref(1)
const containerHeight     = ref(0)
const focusedField        = ref<string | null>(null)

const maxPage = computed(() => {
  const fields = selectedTemplate.value?.fields_schema ?? []
  return Math.max(1, ...fields.map(f => f.page ?? 1))
})

const overlayFields = computed(() =>
  (selectedTemplate.value?.fields_schema ?? []).filter(
    f => (f.page ?? 1) === previewPage.value && f.x !== undefined && f.y !== undefined
  )
)

const overlayFontSizePx = computed(() => {
  if (!containerHeight.value || !selectedTemplate.value) return 12
  const fontSizeMm = (selectedTemplate.value.font_size ?? 10) * (25.4 / 72)
  return containerHeight.value * (fontSizeMm / 297)
})

async function fetchPreview(s3Key: string, page: number) {
  previewLoading.value  = true
  previewError.value    = null
  previewDataUrl.value  = null
  try {
    const resp = await axios.get<{ image: string }>(
      route('templates.preview'),
      { params: { s3_key: s3Key, page } }
    )
    previewDataUrl.value = resp.data.image
  } catch {
    previewError.value = 'Anteprima non disponibile'
  } finally {
    previewLoading.value = false
  }
}

watch(
  [() => selectedTemplate.value?.pdf_template_s3_key, previewPage],
  ([key, page]) => {
    if (key) fetchPreview(key as string, page as number)
    else { previewDataUrl.value = null; previewError.value = null }
  },
  { immediate: true }
)

watch(() => selectedTemplate.value?.id, () => { previewPage.value = 1 })

function updateContainerHeight() {
  if (previewContainerRef.value)
    containerHeight.value = previewContainerRef.value.clientHeight
}

let ro: ResizeObserver | null = null
onMounted(() => {
  if (previewContainerRef.value) {
    ro = new ResizeObserver(updateContainerHeight)
    ro.observe(previewContainerRef.value)
    updateContainerHeight()
  }
})
onBeforeUnmount(() => ro?.disconnect())
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <h1 class="text-base font-semibold text-gray-900">Nuova compilazione</h1>
    </template>

    <div class="flex gap-6 items-start min-h-0">

      <!-- ── LEFT: form ── -->
      <div class="w-[400px] shrink-0 space-y-5">
        <form @submit.prevent="submit" class="space-y-5">

          <!-- Template selector -->
          <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
            <h2 class="text-sm font-semibold text-gray-900">Seleziona template</h2>
            <div class="space-y-1.5">
              <label
                v-for="tpl in templates"
                :key="tpl.id"
                class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition"
                :class="selectedId === tpl.id
                  ? 'border-indigo-500 bg-indigo-50'
                  : 'border-gray-200 hover:bg-gray-50'"
              >
                <input type="radio" :value="tpl.id" v-model="selectedId" class="text-indigo-600 focus:ring-indigo-500"/>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ tpl.name }}</p>
                  <p class="text-xs text-gray-400">{{ tpl.fields_schema?.length ?? 0 }} campi</p>
                </div>
              </label>
            </div>
            <p v-if="templates.length === 0" class="text-sm text-gray-400">
              Nessun template disponibile.
              <a :href="route('templates.create')" class="text-indigo-600 hover:underline">Crea un template</a>.
            </p>
            <p v-if="form.errors.module_template_id" class="text-xs text-red-600">{{ form.errors.module_template_id }}</p>
          </div>

          <!-- Fields -->
          <div
            v-if="selectedTemplate && selectedTemplate.fields_schema?.length"
            class="bg-white rounded-xl border border-gray-200 p-5 space-y-4"
          >
            <h2 class="text-sm font-semibold text-gray-900">Compila i campi</h2>
            <div class="space-y-3">
              <div v-for="field in selectedTemplate.fields_schema" :key="field.name">
                <label class="block text-xs font-medium text-gray-700 mb-1">
                  {{ field.label }}
                  <span v-if="field.required" class="text-red-400 ml-0.5">*</span>
                </label>

                <template v-if="field.type === 'textarea'">
                  <textarea
                    v-model="form.values[field.name]"
                    rows="3"
                    @focus="focusedField = field.name"
                    @blur="focusedField = null"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none"
                  />
                </template>
                <template v-else>
                  <input
                    :type="field.type === 'date' ? 'date' : 'text'"
                    v-model="form.values[field.name]"
                    :maxlength="maxCharsForField(field) > 0 ? maxCharsForField(field) : undefined"
                    @focus="focusedField = field.name"
                    @blur="focusedField = null"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    :class="focusedField === field.name ? 'border-indigo-400' : ''"
                  />
                </template>

                <div v-if="field.type !== 'date' && maxCharsForField(field) > 0" class="flex justify-end mt-0.5">
                  <span
                    class="text-[10px] tabular-nums"
                    :class="{
                      'text-red-500 font-medium': charCount(field) > maxCharsForField(field),
                      'text-amber-500': charCount(field) > maxCharsForField(field) * 0.8 && charCount(field) <= maxCharsForField(field),
                      'text-gray-400': charCount(field) <= maxCharsForField(field) * 0.8,
                    }"
                  >
                    {{ charCount(field) }}/{{ maxCharsForField(field) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div v-else-if="selectedTemplate" class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 text-sm text-amber-800">
            Il template non ha campi definiti.
          </div>

          <!-- Submit -->
          <div class="flex items-center gap-3">
            <button
              type="submit"
              :disabled="!selectedId || form.processing"
              class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              <span v-if="form.processing">Compilazione in corso...</span>
              <span v-else>Compila e salva</span>
            </button>
            <a
              :href="route('compiled.index')"
              class="px-5 py-2.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
            >
              Annulla
            </a>
          </div>

        </form>
      </div>

      <!-- ── RIGHT: live preview ── -->
      <div class="flex-1 sticky top-6 min-w-0">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

          <!-- Preview header -->
          <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-gray-50/80">
            <div class="flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-emerald-400" :class="selectedTemplate ? 'animate-pulse' : 'opacity-30'"></span>
              <span class="text-xs font-medium text-gray-500">Anteprima live</span>
            </div>
            <div v-if="maxPage > 1" class="flex items-center gap-1">
              <button
                type="button"
                :disabled="previewPage <= 1"
                @click="previewPage = Math.max(1, previewPage - 1)"
                class="w-6 h-6 flex items-center justify-center rounded text-gray-500 hover:bg-gray-200 disabled:opacity-30 text-xs transition"
              >◀</button>
              <span class="text-xs text-gray-500 px-1">{{ previewPage }}/{{ maxPage }}</span>
              <button
                type="button"
                :disabled="previewPage >= maxPage"
                @click="previewPage = Math.min(maxPage, previewPage + 1)"
                class="w-6 h-6 flex items-center justify-center rounded text-gray-500 hover:bg-gray-200 disabled:opacity-30 text-xs transition"
              >▶</button>
            </div>
          </div>

          <!-- PDF canvas -->
          <div
            ref="previewContainerRef"
            class="relative bg-gray-100 overflow-hidden"
            style="aspect-ratio: 210 / 297;"
          >
            <!-- Placeholder: no template -->
            <div
              v-if="!selectedTemplate"
              class="absolute inset-0 flex flex-col items-center justify-center gap-3"
            >
              <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <p class="text-sm text-gray-400">Seleziona un template</p>
            </div>

            <!-- Loading -->
            <div v-else-if="previewLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100">
              <svg class="animate-spin w-7 h-7 text-indigo-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
            </div>

            <!-- Error -->
            <div v-else-if="previewError" class="absolute inset-0 flex items-center justify-center">
              <p class="text-sm text-gray-400">{{ previewError }}</p>
            </div>

            <!-- No s3Key -->
            <div v-else-if="!selectedTemplate?.pdf_template_s3_key" class="absolute inset-0 flex items-center justify-center">
              <p class="text-sm text-gray-400 italic">Nessun PDF matrice configurato</p>
            </div>

            <!-- PDF image -->
            <img
              v-if="previewDataUrl"
              :src="previewDataUrl"
              class="w-full h-full object-contain select-none"
              draggable="false"
              alt="Anteprima PDF"
            />

            <!-- Field text overlays -->
            <template v-if="previewDataUrl">
              <!-- Focus highlight -->
              <div
                v-for="field in overlayFields.filter(f => f.name === focusedField)"
                :key="'hl-' + field.name"
                class="absolute pointer-events-none rounded-sm"
                :style="{
                  left:      field.x + '%',
                  top:       `calc(${field.y}% - ${overlayFontSizePx}px)`,
                  width:     field.w + '%',
                  height:    overlayFontSizePx * 1.3 + 'px',
                  background: 'rgba(99,102,241,0.08)',
                  borderBottom: '1.5px solid rgba(99,102,241,0.5)',
                }"
              />

              <!-- Text values -->
              <div
                v-for="field in overlayFields"
                :key="'txt-' + field.name"
                class="absolute overflow-hidden whitespace-nowrap pointer-events-none select-none"
                :style="{
                  left:       field.x + '%',
                  top:        field.y + '%',
                  width:      field.w + '%',
                  fontSize:   overlayFontSizePx + 'px',
                  lineHeight: 1,
                  transform:  `translateY(-${overlayFontSizePx * 0.82}px)`,
                  fontFamily: '\'Helvetica Neue\', Helvetica, Arial, sans-serif',
                  color:      focusedField === field.name ? '#4f46e5' : '#111827',
                  transition: 'color 0.15s',
                }"
              >{{ form.values[field.name] ?? '' }}</div>
            </template>
          </div>

        </div>
      </div>

    </div>
  </AuthenticatedLayout>
</template>
