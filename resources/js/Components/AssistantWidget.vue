<script setup lang="ts">
import { richText } from '@/richText'
import { whatsappUrl } from '@/support'
import axios from 'axios'
import { nextTick, ref } from 'vue'

interface Message { role: 'user' | 'assistant'; content: string }

const open = ref(false)
const chatId = ref<number | null>(null)
const draft = ref('')
const sending = ref(false)
const list = ref<HTMLElement | null>(null)
const messages = ref<Message[]>([
  { role: 'assistant', content: 'Ciao! Sono l\'assistente di Fusion Moduli. Chiedimi come caricare un modulo, sistemare i campi, scaricare i PDF o gestire l\'abbonamento.' },
])

async function scrollDown() {
  await nextTick()
  list.value?.scrollTo({ top: list.value.scrollHeight, behavior: 'smooth' })
}

async function send() {
  const text = draft.value.trim()
  if (!text || sending.value) return

  messages.value.push({ role: 'user', content: text })
  draft.value = ''
  sending.value = true
  scrollDown()

  try {
    const { data } = await axios.post<{ chat_id: number; reply: string }>(route('assistente.store'), {
      chat_id: chatId.value,
      message: text,
      page: window.location.pathname,
    })
    chatId.value = data.chat_id
    messages.value.push({ role: 'assistant', content: data.reply })
  } catch (err) {
    const reply = axios.isAxiosError(err) ? err.response?.data?.reply : null
    messages.value.push({
      role: 'assistant',
      content: reply ?? 'Non riesco a rispondere adesso. Apri una richiesta con **Assistenza** in alto a destra o scrivici su WhatsApp.',
    })
  } finally {
    sending.value = false
    scrollDown()
  }
}
</script>

<template>
  <div class="fixed bottom-5 right-5 z-40 flex flex-col items-end gap-3">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-2 scale-95"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="opacity-0 translate-y-2 scale-95"
    >
      <section
        v-if="open"
        class="w-[22rem] max-w-[calc(100vw-2.5rem)] h-[30rem] max-h-[calc(100vh-7rem)] flex flex-col rounded-2xl bg-white border border-gray-200 shadow-2xl shadow-black/10 overflow-hidden origin-bottom-right"
        aria-label="Assistente Fusion Moduli"
      >
        <header class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
          <div>
            <p class="text-sm font-semibold text-gray-900">Assistente Moduli</p>
            <p class="text-xs text-gray-500">Risponde l'AI · per una persona usa WhatsApp</p>
          </div>
          <button type="button" class="text-gray-400 hover:text-gray-700 text-xl leading-none" aria-label="Chiudi" @click="open = false">×</button>
        </header>

        <div ref="list" class="flex-1 overflow-y-auto px-4 py-3 space-y-3">
          <div
            v-for="(m, i) in messages"
            :key="i"
            class="max-w-[85%] rounded-2xl px-3 py-2 text-sm leading-relaxed"
            :class="m.role === 'user' ? 'ml-auto bg-indigo-600 text-white rounded-br-md' : 'bg-gray-100 text-gray-800 rounded-bl-md'"
            v-html="richText(m.content)"
          />
          <div v-if="sending" class="w-14 rounded-2xl rounded-bl-md bg-gray-100 px-3 py-2.5 flex gap-1">
            <span v-for="d in 3" :key="d" class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" :style="{ animationDelay: `${d * 120}ms` }" />
          </div>
        </div>

        <form class="border-t border-gray-100 p-3 flex gap-2" @submit.prevent="send">
          <input
            v-model="draft"
            type="text"
            maxlength="2000"
            placeholder="Scrivi la tua domanda…"
            class="flex-1 min-w-0 rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            aria-label="La tua domanda"
          />
          <button type="submit" :disabled="sending || !draft.trim()" class="px-3 rounded-xl bg-indigo-600 text-white text-sm font-semibold disabled:opacity-40">Invia</button>
        </form>
        <a :href="whatsappUrl()" target="_blank" rel="noopener" class="block text-center text-xs text-green-700 hover:underline pb-2">
          Preferisci parlare con una persona? WhatsApp
        </a>
      </section>
    </Transition>

    <button
      type="button"
      class="inline-flex items-center gap-2 rounded-full bg-indigo-600 pl-3 pr-4 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-900/20 hover:bg-indigo-700 transition"
      :aria-expanded="open"
      @click="open = !open; open && scrollDown()"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
      </svg>
      {{ open ? 'Chiudi' : 'Hai una domanda?' }}
    </button>
  </div>
</template>
