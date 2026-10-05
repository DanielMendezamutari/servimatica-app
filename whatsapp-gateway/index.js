import makeWASocket, {
  DisconnectReason,
  useMultiFileAuthState,
  Browsers,
  fetchLatestBaileysVersion,
  makeCacheableSignalKeyStore,
} from '@whiskeysockets/baileys'
import fs from 'fs'
import path from 'path'
import pino from 'pino'
import QRCode from 'qrcode'
import qrcodeTerminal from 'qrcode-terminal'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

const AUTH_DIR = path.join(__dirname, 'auth_info')
const PUBLIC_QR_PATH = path.join(__dirname, '..', 'public', 'whatsapp_qr.png')
const WEBHOOK_URL = process.env.WEBHOOK_URL || 'https://servimatica.ribersoft.com/api/webhooks/whatsapp'

let sock = null
const logger = pino({ level: 'silent' })
const msgStore = new Map()

// Evitar que errores no fatales cierren el proceso
process.on('uncaughtException', err => {
  if (err?.message?.includes('Bad MAC')) return
  console.error('⚠️ Error no fatal:', err.message)
})

process.on('unhandledRejection', reason => {
  if (reason?.message?.includes('Bad MAC')) return
})

// Extractor robusto de texto compatible con todas las versiones de WhatsApp
function extractMessageText(message) {
  if (!message) return ''

  // Desempaquetar mensajes temporales o de una sola vez
  const realMsg =
    message.ephemeralMessage?.message ||
    message.viewOnceMessage?.message ||
    message.viewOnceMessageV2?.message ||
    message.documentWithCaptionMessage?.message ||
    message

  return (
    realMsg.conversation ||
    realMsg.extendedTextMessage?.text ||
    realMsg.imageMessage?.caption ||
    realMsg.videoMessage?.caption ||
    realMsg.documentMessage?.caption ||
    ''
  )
}

async function connectToWhatsApp() {
  const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR)
  
  let version = [2, 3000, 1015901307]
  try {
    const latest = await fetchLatestBaileysVersion()
    version = latest.version
    console.log(`📡 Protocolo WhatsApp Web sincronizado: v${version.join('.')}`)
  } catch (e) {
    console.log(`ℹ️ Usando versión base de WhatsApp Web`)
  }

  sock = makeWASocket({
    version,
    auth: {
      creds: state.creds,
      keys: makeCacheableSignalKeyStore(state.keys, logger),
    },
    logger,
    printQRInTerminal: false,
    browser: Browsers.macOS('Desktop'),
    syncFullHistory: false,
    getMessage: async key => {
      return msgStore.get(key.id) || undefined
    },
  })

  sock.ev.on('creds.update', saveCreds)

  sock.ev.on('connection.update', async update => {
    const { connection, lastDisconnect, qr } = update

    if (qr) {
      console.log('\n======================================================')
      console.log('📱 ESCANEA ESTE CÓDIGO QR CON TU WHATSAPP:')
      console.log('O abre en tu navegador: https://servimatica.ribersoft.com/whatsapp_qr.png')
      console.log('======================================================\n')

      qrcodeTerminal.generate(qr, { small: true })

      try {
        await QRCode.toFile(PUBLIC_QR_PATH, qr, {
          width: 380,
          margin: 2,
          color: { dark: '#000000', light: '#ffffff' },
        })
      } catch (err) {}
    }

    if (connection === 'close') {
      const statusCode = lastDisconnect?.error?.output?.statusCode
      const shouldReconnect = statusCode !== DisconnectReason.loggedOut

      console.log(`🔌 Conexión cerrada (Código: ${statusCode}). ¿Reconectar?: ${shouldReconnect}`)

      if (shouldReconnect) {
        setTimeout(connectToWhatsApp, 3000)
      } else {
        console.log('🚪 Sesión cerrada por el usuario. Limpiando credenciales y QR...')
        fs.rmSync(AUTH_DIR, { recursive: true, force: true })
        try { fs.unlinkSync(PUBLIC_QR_PATH) } catch (e) {}
        setTimeout(connectToWhatsApp, 2000)
      }
    } else if (connection === 'open') {
      const connectedNumber = sock.user?.id ? sock.user.id.split(':')[0] : 'Conectado'
      try { fs.unlinkSync(PUBLIC_QR_PATH) } catch (e) {}

      console.log('\n======================================================')
      console.log(`✅ ¡WHATSAPP CONECTADO EXITOSAMENTE AL HOSTING!`)
      console.log(`Número vinculado: +${connectedNumber}`)
      console.log(`Webhook enlazado: ${WEBHOOK_URL}`)
      console.log('👂 Escuchando mensajes entrantes de clientes...')
      console.log('======================================================\n')
    }
  })

  // Escuchar mensajes entrantes (notify y append)
  sock.ev.on('messages.upsert', async ({ messages, type }) => {
    for (const msg of messages) {
      if (msg.key?.id && msg.message) {
        msgStore.set(msg.key.id, msg.message)
        if (msgStore.size > 500) {
          const firstKey = msgStore.keys().next().value
          msgStore.delete(firstKey)
        }
      }

      // Ignorar paquetes de protocolo interno o sincronización técnica de WhatsApp
      if (!msg.message || msg.message.protocolMessage || msg.message.senderKeyDistributionMessage) {
        continue
      }

      const remoteJid = msg.key?.remoteJid || ''
      const isFromMe = msg.key?.fromMe ? true : false

      console.log(`\n🔔 [Mensaje entrante detectado] Remitente: ${remoteJid} | ¿Es de mí mismo?: ${isFromMe ? 'SÍ' : 'NO'}`)

      // Ignorar mensajes enviados por nosotros mismos (evita bucles infinitos)
      if (isFromMe) {
        console.log('   ℹ️ Omitido: enviado desde este mismo número celular.')
        continue
      }

      // Ignorar mensajes de grupos (@g.us), estados (@broadcast) o newsletters
      if (remoteJid.endsWith('@g.us') || remoteJid.includes('@broadcast') || remoteJid.includes('@newsletter')) {
        console.log('   ℹ️ Omitido: es de un grupo o canal.')
        continue
      }

      const text = extractMessageText(msg.message)
      console.log(`   📝 Texto extraído: "${text}"`)

      if (!text || text.trim() === '') {
        console.log('   ⚠️ Texto vacío o tipo de mensaje no compatible.')
        continue
      }

      const pushName = msg.pushName || 'Cliente'
      console.log(`📩 Procesando consulta de ${pushName}: "${text}"`)

      try {
        console.log(`🌐 Consultando IA en el servidor Servimática...`)
        const response = await fetch(WEBHOOK_URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            from: remoteJid,
            text: text.trim(),
            pushName: pushName,
            timestamp: Math.floor(Date.now() / 1000),
          }),
        })

        if (!response.ok) {
          console.error(`❌ Webhook respondió con error HTTP: ${response.status} ${response.statusText}`)
          continue
        }

        const data = await response.json()
        const reply = data?.result?.reply

        if (reply && reply.trim() !== '') {
          console.log(`🤖 IA respondió (${reply.length} caracteres):`)
          console.log(`   "${reply.substring(0, 100)}..."`)
          
          // Enviar respuesta al chat correspondiente (compatible con @s.whatsapp.net y @lid)
          await sock.sendMessage(remoteJid, { text: reply }, { quoted: msg })
          console.log(`📤 Mensaje enviado exitosamente a WhatsApp.`)
        } else {
          console.log(`ℹ️ Webhook procesado sin mensaje saliente (status: ${data?.status || 'ok'}).`)
        }
      } catch (err) {
        console.error(`❌ Error al conectar con webhook de Servimática:`, err.message)
      }
    }
  })
}

connectToWhatsApp()
