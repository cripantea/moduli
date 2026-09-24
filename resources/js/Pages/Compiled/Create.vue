<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'

interface FieldSchema {
  name: string
  label: string
  type: string
  required: boolean
  w?: number
}

interface Template {
  id: number
  name: string
  fields_schema: FieldSchema[] | null
  font_size: number
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
</script>

<template>
  <AuthenticatedLayout>
    <template #header>
      <h1 class="text-base font-semibold text-gray-900">Nuova compilazione</h1>
    </template>

    <div class="max-w-2xl">
      <form @submit.prevent="submit" class="space-y-6">

        <!-- Template selector -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
          <h2 class="text-sm font-semibold text-gray-900">Seleziona template</h2>
          <div class="grid grid-cols-1 gap-2">
            <label
              v-for="tpl in templates"
              :key="tpl.id"
              class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition"
              :class="selectedId === tpl.id ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:bg-gray-50'"
            >
              <input type="radio" :value="tpl.id" v-model="selectedId" class="text-indigo-600 focus:ring-indigo-500"/>
              <div>
                <p class="text-sm font-medium text-gray-900">{{ tpl.name }}</p>
                <p class="text-xs text-gray-400">{{ tpl.fields_schema?.length ?? 0 }} campi</p>
              </div>
            </label>
          </div>
          <p v-if="templates.length === 0" class="text-sm text-gray-400">
            Nessun template disponibile. <a :href="route('templates.create')" class="text-indigo-600 hover:underline">Crea un template</a>.
          </p>
          <p v-if="form.errors.module_template_id" class="text-xs text-red-600">{{ form.errors.module_template_id }}</p>
        </div>

        <!-- Fields -->
        <div v-if="selectedTemplate && selectedTemplate.fields_schema?.length" class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
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
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none"
                />
                <div v-if="maxCharsForField(field) > 0" class="flex justify-end mt-0.5">
                  <span
                    class="text-[10px] tabular-nums"
                    :class="{
                      'text-red-500 font-medium': charCount(field) > maxCharsForField(field),
                      'text-amber-500': charCount(field) > maxCharsForField(field) * 0.8 && charCount(field) <= maxCharsForField(field),
                      'text-gray-400': charCount(field) <= maxCharsForField(field) * 0.8,
                    }"
                  >
                    {{ charCount(field) }}/{{ maxCharsForField(field) }}
                    <template v-if="charCount(field) > maxCharsForField(field)"> — verrà troncato nel PDF</template>
                  </span>
                </div>
              </template>
              <template v-else>
                <input
                  :type="field.type === 'date' ? 'date' : 'text'"
                  v-model="form.values[field.name]"
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                />
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
                    <template v-if="charCount(field) > maxCharsForField(field)"> — verrà troncato nel PDF</template>
                  </span>
                </div>
              </template>
            </div>
          </div>
        </div>

        <div v-else-if="selectedTemplate" class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-4 text-sm text-amber-800">
          Il template non ha campi definiti. Il PDF verrà compilato senza sostituzione di testo.
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-3">
          <button
            type="submit"
            :disabled="!selectedId || form.processing"
            class="px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            <span v-if="form.processing">Compilazione in corso...</span>
            <span v-else>Compila e salva</span>
          </button>
          <a :href="route('compiled.index')" class="px-5 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Annulla</a>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
