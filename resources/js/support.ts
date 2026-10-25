// Assistenza: WhatsApp Fusion (risposte dal CRM) e ticket salvati nel superadmin.
const WHATSAPP_NUMBER = '393514514462'

export function whatsappUrl(text = 'Ciao, ho una domanda su Fusion Moduli') {
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(text)}`
}
