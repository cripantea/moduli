// Pixel Meta (registrazioni e pagamenti delle campagne) e Microsoft Clarity (mappe di calore e
// registrazioni delle visite). Si caricano solo se il visitatore accetta i cookie; senza consenso non parte nulla.
// I contatori del funnel (funnel()) sono anonimi e restano sul nostro server.

type Fbq = ((...args: unknown[]) => void) & { callMethod?: (...a: unknown[]) => void; queue: unknown[]; loaded: boolean; version: string; push: unknown }

type Clarity = ((...args: unknown[]) => void) & { q?: unknown[] }

declare global {
  interface Window { fbq?: Fbq; _fbq?: Fbq; clarity?: Clarity }
}

export interface TrackEvent {
  event: string
  params?: Record<string, unknown>
  eventID?: string
  custom?: boolean
}

const CONSENT_KEY = 'cookie-consenso'

export const pixelId = document.querySelector<HTMLMetaElement>('meta[name="meta-pixel"]')?.content || ''
const clarityId = document.querySelector<HTMLMetaElement>('meta[name="clarity"]')?.content || ''
export const trackingEnabled = !!(pixelId || clarityId)

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
  consentCookie(value)
  if (value === 'si') {
    loadPixel()
    loadClarity()
  }
}

// Anche come cookie: il server manda gli eventi alla Conversions API solo con consenso.
function consentCookie(value: 'si' | 'no') {
  document.cookie = `${CONSENT_KEY}=${value}; path=/; max-age=31536000; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`
}

// Chi aveva già scelto prima che esistesse il cookie lo riceve alla prima visita.
const saved = consent()
if (saved && !document.cookie.split('; ').some((c) => c.startsWith(`${CONSENT_KEY}=`))) consentCookie(saved)

function loadClarity() {
  if (!clarityId || window.clarity) return

  const clarity = function (...args: unknown[]) { (clarity.q = clarity.q || []).push(args) } as Clarity
  window.clarity = clarity
  const script = document.createElement('script')
  script.async = true
  script.src = `https://www.clarity.ms/tag/${clarityId}`
  document.head.appendChild(script)
  clarity('consent')
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
  if (consent() !== 'si') return false
  loadClarity()
  if (!pixelId) return false
  loadPixel()
  return true
}

export function pageView() {
  if (ready()) window.fbq!('track', 'PageView')
}

export function track({ event, params, eventID, custom }: TrackEvent) {
  if (!ready()) return

  // Un acquisto si conta una volta sola anche se la pagina viene ricaricata.
  if (eventID) {
    const key = `tracciato-${eventID}`
    if (storage('get', key)) return
    storage('set', key, '1')
  }

  window.fbq!(custom ? 'trackCustom' : 'track', event, params ?? {}, eventID ? { eventID } : undefined)
}

// Passo del funnel fatto nel browser: contatore anonimo sul nostro server, una volta per visita.
// Restituisce true solo la prima volta, così anche l'evento Meta collegato parte una volta.
export function funnel(step: 'prezzo' | 'clic'): boolean {
  const key = `funnel-${step}`
  try {
    if (sessionStorage.getItem(key)) return false
    sessionStorage.setItem(key, '1')
  } catch { /* senza sessionStorage conta il server, una volta per sessione */ }
  window.axios?.post('/funnel', { step }).catch(() => {})
  return true
}
