import { createRouter, createWebHistory } from 'vue-router'
import { setupGuards } from './guards'

const router = createRouter({
  history: createWebHistory('/'),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    {
      path: '/login',
      component: () => import('@/layouts/blank.vue'),
      children: [{ path: '', name: 'login', component: () => import('@/pages/login.vue'), meta: { unauthenticatedOnly: true } }],
    },
    {
      path: '/',
      component: () => import('@/layouts/default.vue'),
      children: [
        { path: '', name: 'root', component: () => import('@/pages/index.vue'), meta: { action: 'read', subject: 'Dashboard' } },
        { path: 'users', name: 'users', component: () => import('@/pages/users/index.vue'), meta: { action: 'manage', subject: 'User' } },
        { path: 'not-authorized', name: 'not-authorized', component: () => import('@/pages/not-authorized.vue'), meta: { action: 'read', subject: 'Dashboard' } },
        { path: ':pathMatch(.*)*', name: 'not-found', component: () => import('@/pages/[...error].vue'), meta: { action: 'read', subject: 'Dashboard' } },
      ],
    },
  ],
})

setupGuards(router)
export { router }
export default function (app) {
  app.use(router)
}
