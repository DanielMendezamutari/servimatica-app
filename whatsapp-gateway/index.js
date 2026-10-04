import makeWASocket, {
  DisconnectReason,
  useMultiFileAuthState,
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

async function connectToWhatsApp() {
  const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR)

  sock = makeWASocket({
    auth: state,
    logger,
    printQRInTerminal: false,
    browser: ['Servimática Bot', 'Chrome', '120.0.0'],
  })

  sock.ev.on('creds.update', saveCreds)

  sock.ev.on('connection.update', async update => {
    const { connection, lastDisconnect, qr } = update

    if (qr) {
      console.log('\n======================================================')
      console.log('📱 ESCANEA ESTE CÓDIGO QR CON TU WHATSAPP:')
      console.log('O abre en tu navegador: https://servimatica.ribersoft.com/whatsapp_qr.png')
      console.log('======================================================\n')
      
      // 1. Mostrar en consola
      qrcodeTerminal.generate(qr, { small: true })

      // 2. Guardar imagen PNG en la carpeta pública del sitio web
      try {
        await QRCode.toFile(PUBLIC_QR_PATH, qr, {
          width: 380,
          margin: 2,
          color: { dark: '#000000', light: '#ffffff' },
        })
      } catch (err) {
        // Ignorar si no tiene permisos de escritura en public
      }
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

      // Eliminar imagen del QR por seguridad
      try { fs.unlinkSync(PUBLIC_QR_PATH) } catch (e) {}

      console.log('\n======================================================')
      console.log(`✅ ¡WHATSAPP CONECTADO EXITOSAMENTE AL HOSTING!`)
      console.log(`Número vinculado: +${connectedNumber}`)
      console.log(`Webhook enlazado: ${WEBHOOK_URL}`)
      console.log('======================================================\n')
    }
  })

  // Escuchar mensajes entrantes
  sock.ev.on('messages.upsert', async ({ messages, type }) => {
    if (type !== 'notify') return

    for (const msg of messages) {
      if (!msg.message || msg.key.fromMe) continue
      const remoteJid = msg.key.remoteJid || ''
      if (remoteJid.endsWith('@g.us')) continue // Ignorar grupos

      const senderPhone = remoteJid.replace('@s.whatsapp.net', '')
      const text =
        msg.message.conversation ||
        msg.message.extendedTextMessage?.text ||
        msg.message.imageMessage?.caption ||
        ''

      if (!text || text.trim() === '') continue

      const pushName = msg.pushName || 'Cliente'
      console.log(`📩 Mensaje recibido de +${senderPhone} (${pushName}): "${text}"`)

      try {
        // Llamar al webhook nativo usando fetch global de Node 18/20
        const response = await fetch(WEBHOOK_URL, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            from: senderPhone,
            text: text,
            pushName: pushName,
            timestamp: Math.floor(Date.now() / 1000),
          }),
        })

        const data = await response.json()
        const reply = data?.result?.reply

        if (reply && reply.trim() !== '') {
          console.log(`🤖 IA respondió a +${senderPhone}: "${reply.substring(0, 80)}..."`)
          await sock.sendMessage(remoteJid, { text: reply })
        }
      } catch (err) {
        console.error(`❌ Error al conectar con webhook de Servimática:`, err.message)
      }
    }
  })
}

// Iniciar conexión
connectToWhatsApp()
