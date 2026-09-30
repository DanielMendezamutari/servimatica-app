import { $api } from '@/utils/api'
import { accessToken, clearSession, scheduleExpiration, updateIdentity } from '@/utils/session'
import { ability } from '@/plugins/casl/ability'

// Adaptado de admin-full-version/src/plugins/1.router/guards.js.
export const setupGuards = router => {
  router.beforeEach(async to => {
    if (to.meta.public) return
    if (!accessToken()) {
      clearSession()
      
      return to.path === '/login' ? undefined : '/login'
    }
    try {
      updateIdentity(await $api('/auth/me'))
      scheduleExpiration()
    } catch {
      clearSession()
      
      return to.path === '/login' ? undefined : '/login'
    }
    if (to.path === '/login') return '/'
    const rule = [...to.matched].reverse().find(route => route.meta.action && route.meta.subject)
    if (!rule || !ability.can(rule.meta.action, rule.meta.subject)) return '/not-authorized'
  })

  const verifyRestoredPage = async () => {
    if (router.currentRoute.value.path === '/login') return
    if (!accessToken()) {
      clearSession()
      await router.replace('/login')
      
      return
    }
    try {
      updateIdentity(await $api('/auth/me'))
      scheduleExpiration()
      if (router.currentRoute.value.path.startsWith('/users') && !ability.can('manage', 'User'))
        await router.replace('/not-authorized')
    } catch {
      clearSession()
      await router.replace('/login')
    }
  }

  window.addEventListener('pageshow', verifyRestoredPage)
  window.addEventListener('focus', verifyRestoredPage)
}
