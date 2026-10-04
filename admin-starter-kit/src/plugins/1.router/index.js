import { createRouter, createWebHistory } from 'vue-router'
import { setupGuards } from './guards'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    {
      path: '/login',
      component: () => import('@/layouts/blank.vue'),
      children: [{ path: '', name: 'login', component: () => import('@/pages/login.vue'), meta: { unauthenticatedOnly: true } }],
    },
    {
      path: '/catalogo',
      component: () => import('@/layouts/blank.vue'),
      children: [
        {
          path: '',
          name: 'public-catalog',
          component: () => import('@/pages/catalog/index.vue'),
          meta: { public: true },
        },
      ],
    },
    {
      path: '/catalog',
      redirect: '/catalogo',
    },
    {
      path: '/',

      component: () => import('@/layouts/default.vue'),
      children: [
        { path: '', name: 'root', component: () => import('@/pages/index.vue'), meta: { action: 'read', subject: 'Dashboard' } },
        { path: 'users', name: 'users', component: () => import('@/pages/users/index.vue'), meta: { action: 'manage', subject: 'User' } },
        { path: 'categories', name: 'categories', component: () => import('@/pages/categories/index.vue'), meta: { action: 'manage', subject: 'Category' } },
        { path: 'brands', redirect: { path: '/categories', query: { tab: 'brands' } } },
        { path: 'products', name: 'products', component: () => import('@/pages/products/index.vue'), meta: { action: 'read', subject: 'Product' } },
        { path: 'products/history/:id', name: 'products-history', component: () => import('@/pages/products/history/[id].vue'), meta: { action: 'manage', subject: 'Product' } },
        { path: 'pos', name: 'pos', component: () => import('@/pages/pos/index.vue'), meta: { action: 'read', subject: 'Pos' } },
        { path: 'quotes', name: 'quotes', component: () => import('@/pages/quotes/index.vue'), meta: { action: 'read', subject: 'Quote' } },
        { path: 'clients', name: 'clients', component: () => import('@/pages/clients/index.vue'), meta: { action: 'read', subject: 'Client' } },
        { path: 'clients/:id', name: 'clients-detail', component: () => import('@/pages/clients/[id].vue'), meta: { action: 'read', subject: 'Client' } },
        { path: 'sales', name: 'sales', component: () => import('@/pages/sales/index.vue'), meta: { action: 'read', subject: 'Sale' } },
        { path: 'sales/returns', name: 'sales-returns', component: () => import('@/pages/sales/returns.vue'), meta: { action: 'read', subject: 'Sale' } },
        { path: 'purchases', name: 'purchases', component: () => import('@/pages/purchases/index.vue'), meta: { action: 'manage', subject: 'Purchase' } },
        { path: 'purchases/create', name: 'purchases-create', component: () => import('@/pages/purchases/create.vue'), meta: { action: 'manage', subject: 'Purchase' } },
        { path: 'suppliers', name: 'suppliers', component: () => import('@/pages/suppliers/index.vue'), meta: { action: 'manage', subject: 'Supplier' } },
        { path: 'cash-shifts', name: 'cash-shifts', component: () => import('@/pages/cash-shifts/index.vue'), meta: { action: 'read', subject: 'CashShift' } },
        { path: 'payment-methods', name: 'payment-methods', component: () => import('@/pages/payment-methods/index.vue'), meta: { action: 'manage', subject: 'PaymentMethod' } },
        { path: 'settings/company', name: 'settings-company', component: () => import('@/pages/settings/company.vue'), meta: { action: 'manage', subject: 'all' } },
        { path: 'audit/logins', name: 'audit-logins', component: () => import('@/pages/audit/logins.vue'), meta: { action: 'manage', subject: 'User' } },
        { path: 'reports/kardex', name: 'reports-kardex', component: () => import('@/pages/reports/kardex.vue'), meta: { action: 'read', subject: 'Product' } },
        { path: 'reports/profitability', name: 'reports-profitability', component: () => import('@/pages/reports/profitability.vue'), meta: { action: 'manage', subject: 'all' } },
        { path: 'reports/inventory-valuation', name: 'reports-inventory-valuation', component: () => import('@/pages/reports/inventory-valuation.vue'), meta: { action: 'manage', subject: 'all' } },
        { path: 'whatsapp-bot', name: 'whatsapp-bot', component: () => import('@/pages/whatsapp-bot/index.vue'), meta: { action: 'manage', subject: 'all' } },
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
