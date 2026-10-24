// Pixel Meta: misura registrazioni e pagamenti delle campagne.
// Si carica solo se il visitatore accetta i cookie dal banner; senza consenso non parte nulla.

type Fbq = ((...args: unknown[]) => void) & { callMethod?: (...a: unknown[]) => void; queue: unknown[]; loaded: boolean; version: string; push: unknown }

declare global {
  interface Window { fbq?: Fbq; _fbq?: Fbq }
}

export interface TrackEvent {
  event: string
  params?: Record<string, unknown>
  eventID?: string
}

const CONSENT_KEY = 'cookie-consenso'

export const pixelId = document.querySelector<HTMLMetaElement>('meta[name="meta-pixel"]')?.content || ''

function storage(action: 'get', key: string): string | null
function storage(action: 'set', key: string, value: string): null
function storage(action: 'get' | 'set', key: string, value = ''): string | null {
  try {
    if (action === 'get') return window.localStorage.getItem(key)
    window.localStorage.setItem(key, value)
  } catch { /* storage non disponibile (es. navigazione privata): si comporta come senza consenso */ }
  return null
}

export function consent(): 'si' | 'no' | null {
  const value = storage('get', CONSENT_KEY)
  return value === 'si' || value === 'no' ? value : null
}

export function setConsent(value: 'si' | 'no') {
  storage('set', CONSENT_KEY, value)
  if (value === 'si') loadPixel()
}

function loadPixel() {
  if (!pixelId || window.fbq) return

  const fbq = function (...args: unknown[]) {
    fbq.callMethod ? fbq.callMethod(...args) : fbq.queue.push(args)
  } as Fbq
  fbq.queue = []
  fbq.loaded = true
  fbq.version = '2.0'
  fbq.push = fbq
  window.fbq = window._fbq = fbq

  const script = document.createElement('script')
  script.async = true
  script.src = 'https://connect.facebook.net/it_IT/fbevents.js'
  document.head.appendChild(script)

  fbq('init', pixelId)
}

function ready(): boolean {
  if (!pixelId || consent() !== 'si') return false
  loadPixel()
  return true
}

export function pageView() {
  if (ready()) window.fbq!('track', 'PageView')
}

export function track({ event, params, eventID }: TrackEvent) {
  if (!ready()) return

  // Un acquisto si conta una volta sola anche se la pagina viene ricaricata.
  if (eventID) {
    const key = `tracciato-${eventID}`
    if (storage('get', key)) return
    storage('set', key, '1')
  }

  window.fbq!('track', event, params ?? {}, eventID ? { eventID } : undefined)
}
