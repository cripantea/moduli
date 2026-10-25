<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import SuperadminLayout from '@/Layouts/SuperadminLayout.vue'
import { richText } from '@/richText'

interface Message { id: number; role: 'user' | 'assistant'; content: string; created_at: string }
interface Chat {
  id: number
  page: string | null
  updated_at: string
  messages_count?: number
  messages: Message[]
  tenant: { id: number; name: string } | null
  user: { id: number; name: string; email: string } | null
}

defineProps<{
  chats: { data: Chat[]; links: { url: string | null; label: string; active: boolean }[]; last_page: number } | null
  chat: Chat | null
}>()

const date = (iso: string) => new Date(iso).toLocaleString('it-IT', { dateStyle: 'short', timeStyle: 'short' })
</script>

<template>
  <Head title="Chat assistente — Superadmin" />

  <SuperadminLayout title="Chat assistente AI">
    <div class="p-6 max-w-4xl space-y-4">
      <!-- Elenco -->
      <template v-if="chats">
        <p v-if="!chats.data.length" class="text-sm text-slate-500 py-10 text-center">Ancora nessuna conversazione.</p>
        <Link
          v-for="c in chats.data"
          :key="c.id"
          :href="route('superadmin.chats.show', c.id)"
          class="block bg-white rounded-xl border border-slate-200 p-4 hover:border-slate-300 transition"
        >
          <p class="text-sm text-slate-900 truncate">{{ c.messages[0]?.content ?? '—' }}</p>
          <p class="mt-1 text-xs text-slate-500">
            {{ date(c.updated_at) }} · {{ c.tenant?.name ?? '—' }} · {{ c.user?.name ?? '—' }} · {{ c.messages_count }} messaggi
            <span v-if="c.page"> · da {{ c.page }}</span>
          </p>
        </Link>
        <div v-if="chats.last_page > 1" class="flex gap-1">
          <Link
            v-for="l in chats.links"
            :key="l.label"
            :href="l.url ?? ''"
            class="px-2.5 py-1 text-xs rounded border"
            :class="[l.active ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-200 text-slate-600', !l.url && 'pointer-events-none opacity-40']"
            v-html="l.label"
          />
        </div>
      </template>

      <!-- Dettaglio -->
      <template v-else-if="chat">
        <Link :href="route('superadmin.chats.index')" class="text-sm text-indigo-600 hover:underline">← Tutte le chat</Link>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
          <p class="text-xs text-slate-500 mb-4">
            {{ chat.tenant?.name ?? '—' }} ·
            <a v-if="chat.user" :href="`mailto:${chat.user.email}`" class="text-indigo-600 hover:underline">{{ chat.user.name }} &lt;{{ chat.user.email }}&gt;</a>
            <span v-if="chat.page"> · aperta da {{ chat.page }}</span>
          </p>
          <div class="space-y-3">
            <div
              v-for="m in chat.messages"
              :key="m.id"
              class="max-w-[80%] rounded-2xl px-3 py-2 text-sm"
              :class="m.role === 'user' ? 'ml-auto bg-indigo-600 text-white' : 'bg-slate-100 text-slate-800'"
            >
              <div v-html="richText(m.content)" />
              <p class="mt-1 text-[10px] opacity-60">{{ date(m.created_at) }}</p>
            </div>
          </div>
        </div>
      </template>
    </div>
  </SuperadminLayout>
</template>
