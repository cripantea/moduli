<script setup lang="ts">
import Modal from '@/Components/Modal.vue'
import { whatsappUrl } from '@/support'
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const open = ref(false)
const form = useForm({ subject: '', message: '', page: '' })

function show() {
  form.page = window.location.pathname
  open.value = true
}

function submit() {
  form.post(route('assistenza.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      open.value = false
    },
  })
}
</script>

<template>
  <button
    type="button"
    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
    @click="show"
  >
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    Assistenza
  </button>

  <Modal :show="open" max-width="lg" @close="open = false">
    <form class="p-6" @submit.prevent="submit">
      <h2 class="text-lg font-bold text-gray-900">Apri una richiesta di assistenza</h2>
      <p class="mt-1 text-sm text-gray-600">Ti rispondiamo via email. Se è urgente scrivici su WhatsApp.</p>

      <label class="mt-5 block text-sm font-medium text-gray-700" for="ticket-subject">Oggetto</label>
      <input
        id="ticket-subject"
        v-model="form.subject"
        type="text"
        maxlength="150"
        required
        placeholder="Es. un campo finisce nel posto sbagliato"
        class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
      />
      <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>

      <label class="mt-4 block text-sm font-medium text-gray-700" for="ticket-message">Descrivi il problema</label>
      <textarea
        id="ticket-message"
        v-model="form.message"
        rows="5"
        maxlength="5000"
        required
        class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
      />
      <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>

      <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <a :href="whatsappUrl()" target="_blank" rel="noopener" class="text-sm font-medium text-green-700 hover:underline">
          Preferisci WhatsApp?
        </a>
        <div class="flex gap-2">
          <button type="button" class="px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100" @click="open = false">Annulla</button>
          <button type="submit" :disabled="form.processing" class="px-4 py-2 rounded-lg bg-indigo-600 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
            Invia richiesta
          </button>
        </div>
      </div>
    </form>
  </Modal>
</template>
