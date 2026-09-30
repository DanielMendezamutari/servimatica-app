import { ref } from 'vue'
import { parse, serialize } from 'cookie-es'
import { ability } from '@/plugins/casl/ability'

export const currentUser = ref(null)
let expiryTimer
const cookieOptions = () => ({ path: '/', sameSite: 'strict', secure: location.protocol === 'https:' })
export function accessToken() {
  return parse(document.cookie).accessToken || null
}
export function clearSession() {
  clearTimeout(expiryTimer)
  for (const key of ['accessToken', 'userData', 'userAbilityRules'])
    document.cookie = serialize(key, '', { ...cookieOptions(), maxAge: 0 })
  currentUser.value = null
  ability.update([])
}
export function updateIdentity(data) {
  currentUser.value = data.userData
  ability.update(data.userAbilityRules)
}
export function saveSession(data) {
  clearSession()
  for (const key of ['accessToken', 'userData', 'userAbilityRules']) {
    const value = typeof data[key] === 'string' ? data[key] : JSON.stringify(data[key])

    document.cookie = serialize(key, value, { ...cookieOptions(), maxAge: data.expiresIn })
  }
  updateIdentity(data)
  scheduleExpiration()
}
export function scheduleExpiration() {
  clearTimeout(expiryTimer)

  const token = accessToken()
  if (!token) return
  try {
    const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')))
    const remaining = payload.exp * 1000 - Date.now()
    if (remaining <= 0) throw new Error('expired')
    expiryTimer = setTimeout(() => {
      clearSession()
      location.replace('/login')
    }, remaining)
  } catch {
    clearSession()
  }
}
