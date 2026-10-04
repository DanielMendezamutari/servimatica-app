import makeWASocket, {
  DisconnectReason,
  useMultiFileAuthState,
} from '@whiskeysockets/baileys'
import axios from 'axios'
import express from 'express'
import fs from 'fs'
import path from 'path'
import pino from 'pino'
import QRCode from 'qrcode'
import qrcodeTerminal from 'qrcode-terminal'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

const AUTH_DIR = path.join(__dirname, 'auth_info')
const WEBHOOK_URL = process.env.WEBHOOK_URL || 'https://servimatica.ribersoft.com/api/webhooks/whatsapp'
const PORT = process.env.PORT || 3333

const app = express()
app.use(express.json())

let sock = null
let qrCodeData = null
let connectionStatus = 'Esperando generar QR...'
let connectedNumber = null

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
      qrCodeData = qr
      connectionStatus = 'Listo para escanear QR'
      console.log('\n======================================================')
      console.log('📱 ESCANEA ESTE CÓDIGO QR CON TU WHATSAPP:')
      console.log('O abre en tu navegador: http://localhost:3333')
      console.log('======================================================\n')
      qrcodeTerminal.generate(qr, { small: true })
    }

    if (connection === 'close') {
      const statusCode = lastDisconnect?.error?.output?.statusCode
      const shouldReconnect = statusCode !== DisconnectReason.loggedOut

      connectionStatus = 'Desconectado'
      connectedNumber = null
      qrCodeData = null

      console.log(`🔌 Conexión cerrada (Código: ${statusCode}). ¿Reconectar?: ${shouldReconnect}`)

      if (shouldReconnect) {
        setTimeout(connectToWhatsApp, 3000)
      } else {
        console.log('🚪 Sesión cerrada por el usuario. Limpiando credenciales...')
        fs.rmSync(AUTH_DIR, { recursive: true, force: true })
        setTimeout(connectToWhatsApp, 2000)
      }
    } else if (connection === 'open') {
      connectionStatus = 'Conectado'
      qrCodeData = null
      connectedNumber = sock.user?.id ? sock.user.id.split(':')[0] : 'Conectado'

      console.log('\n======================================================')
      console.log(`✅ ¡WHATSAPP CONECTADO EXITOSAMENTE!`)
      console.log(`Número vinculado: +${connectedNumber}`)
      console.log(`Webhook enlazado: ${WEBHOOK_URL}`)
      console.log('======================================================\n')
    }
  })

  // Escuchar mensajes entrantes
  sock.ev.on('messages.upsert', async ({ messages, type }) => {
    if (type !== 'notify') return

    for (const msg of messages) {
      // Ignorar mensajes enviados por uno mismo o de grupos
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
        // Enviar al webhook de Servimática
        const response = await axios.post(
          WEBHOOK_URL,
          {
            from: senderPhone,
            text: text,
            pushName: pushName,
            timestamp: Math.floor(Date.now() / 1000),
          },
          {
            headers: { 'Content-Type': 'application/json' },
            timeout: 35000,
          }
        )

        const reply = response.data?.result?.reply

        if (reply && reply.trim() !== '') {
          console.log(`🤖 IA respondió a +${senderPhone}: "${reply.substring(0, 80)}..."`)
          // Enviar respuesta al cliente por WhatsApp
          await sock.sendMessage(remoteJid, { text: reply })
        }
      } catch (err) {
        console.error(`❌ Error al consultar webhook de Servimática:`, err.message)
      }
    }
  })
}

// Ruta Web interactiva para ver el QR o desconectar en 1 clic
app.get('/', async (req, res) => {
  let qrImageHtml = ''
  if (qrCodeData) {
    const qrDataUrl = await QRCode.toDataURL(qrCodeData, { width: 300 })
    qrImageHtml = `
      <div style="text-align: center; margin: 20px 0;">
        <img src="${qrDataUrl}" style="border: 4px solid #128c7e; border-radius: 12px; padding: 10px; background: white;" />
        <p style="color: #666; font-size: 14px; margin-top: 10px;">Abre WhatsApp en tu celular > <b>Dispositivos vinculados</b> > <b>Vincular un dispositivo</b> y escanea este código.</p>
      </div>
    `
  }

  const isConnected = connectionStatus === 'Conectado'

  res.send(`
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Gateway WhatsApp - Servimática</title>
      <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 32px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 480px; width: 100%; text-align: center; }
        .badge { display: inline-block; padding: 6px 16px; border-radius: 20px; font-weight: 600; font-size: 14px; margin-bottom: 16px; }
        .connected { background: #e8f5e9; color: #2e7d32; }
        .waiting { background: #fff3e0; color: #e65100; }
        .btn-logout { background: #d32f2f; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-logout:hover { background: #b71c1c; }
        .btn-refresh { background: #128c7e; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; margin-top: 15px; }
      </style>
      ${!isConnected ? '<meta http-equiv="refresh" content="5">' : ''}
    </head>
    <body>
      <div class="card">
        <h2 style="margin: 0 0 8px 0; color: #111;">Gateway WhatsApp</h2>
        <p style="color: #666; font-size: 14px; margin: 0 0 20px 0;">Servimática AI Bot • Conexión Física</p>
        
        <span class="badge ${isConnected ? 'connected' : 'waiting'}">
          ${isConnected ? '🟢 CONECTADO: +' + connectedNumber : '🟡 ' + connectionStatus}
        </span>

        ${
          isConnected
            ? `
          <div style="background: #f9fafb; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: left; font-size: 14px; color: #444;">
            <p style="margin: 6px 0;"><b>Estado:</b> El bot está respondiendo en vivo.</p>
            <p style="margin: 6px 0;"><b>Webhook:</b> <code>${WEBHOOK_URL}</code></p>
            <p style="margin: 6px 0; color: #2e7d32;">✨ Pídele a un amigo que escriba a tu WhatsApp para probarlo.</p>
          </div>
          <form method="POST" action="/logout">
            <button type="submit" class="btn-logout">Cerrar Sesión / Desconectar WhatsApp</button>
          </form>
        `
            : `
          ${qrImageHtml}
          <button class="btn-refresh" onclick="location.reload()">Actualizar pantalla</button>
        `
        }
      </div>
    </body>
    </html>
  `)
})

// Ruta para cerrar sesión
app.post('/logout', async (req, res) => {
  try {
    if (sock) {
      await sock.logout()
    }
  } catch (e) {}
  fs.rmSync(AUTH_DIR, { recursive: true, force: true })
  res.redirect('/')
})

app.listen(PORT, () => {
  console.log(`🌐 Panel Gateway ejecutándose en: http://localhost:${PORT}`)
  connectToWhatsApp()
})
