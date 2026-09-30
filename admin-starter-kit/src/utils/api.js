import { ofetch } from 'ofetch'
import { accessToken, clearSession } from './session'

export const $api = ofetch.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  retry: 0,
  timeout: 15000,
  onRequest({ options }) {
    const headers = new Headers(options.headers)

    headers.set('Accept', 'application/json')

    const token = accessToken()
    if (token) headers.set('Authorization', `Bearer ${token}`)
    options.headers = headers
  },
  onResponseError({ request, response }) {
    const isLogin = String(request).includes('/auth/login')
    const isInactive = response.status === 403 && String(request).includes('/auth/me')
    if (!isLogin && (response.status === 401 || isInactive)) {
      clearSession()
      if (location.pathname !== '/login') location.replace('/login')
    }
  },
})
export function apiError(error) {
  if (error?.status >= 500)
    return 'El servidor tuvo un error al procesar la solicitud. Intente nuevamente.'

  return error?.data?.message || 'No se pudo conectar con el servidor. Intente nuevamente.'
}
