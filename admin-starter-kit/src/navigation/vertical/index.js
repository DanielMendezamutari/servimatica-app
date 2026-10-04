export default [
  {
    title: 'Inicio',
    to: { name: 'root' },
    icon: { icon: 'ri-home-smile-2-line' },
    action: 'read',
    subject: 'Dashboard',
  },

  // 1. Operaciones directas de alta frecuencia
  { heading: 'Operaciones' },
  {
    title: 'Punto de Venta (POS)',
    to: { name: 'pos' },
    icon: { icon: 'ri-shopping-cart-2-line' },
    action: 'read',
    subject: 'Pos',
  },
  {
    title: 'Cotizaciones / Proformas',
    to: { name: 'quotes' },
    icon: { icon: 'ri-file-list-3-line' },
    action: 'read',
    subject: 'Quote',
  },
  {
    title: 'Clientes (CRM)',
    to: { name: 'clients' },
    icon: { icon: 'ri-user-star-line' },
    action: 'read',
    subject: 'Client',
  },
  {
    title: 'Bot WhatsApp IA',
    to: { name: 'whatsapp-bot' },
    icon: { icon: 'ri-whatsapp-line' },
    action: 'manage',
    subject: 'all',
  },

  // 2. Módulos y Gestión (Grupos Colapsables)
  { heading: 'Gestión del Negocio' },
  {
    title: 'Operaciones de Caja',
    icon: { icon: 'ri-safe-2-line' },
    children: [
      {
        title: 'Ventas en Tienda',
        to: { name: 'sales' },
        action: 'read',
        subject: 'Sale',
      },
      {
        title: 'Devoluciones y Garantías',
        to: { name: 'sales-returns' },
        action: 'read',
        subject: 'Sale',
      },
      {
        title: 'Caja Chica y Turnos',
        to: { name: 'cash-shifts' },
        action: 'read',
        subject: 'CashShift',
      },
    ],
  },
  {
    title: 'Inventario y Stock',
    icon: { icon: 'ri-box-3-line' },
    children: [
      {
        title: 'Catálogo de Productos',
        to: { name: 'products' },
        action: 'read',
        subject: 'Product',
      },
      {
        title: 'Vitrina TikTok Live (3D)',
        href: '/catalogo',
        target: '_blank',
        action: 'read',
        subject: 'Product',
      },
      {
        title: 'Compras y Recepción',

        to: { name: 'purchases' },
        action: 'manage',
        subject: 'Purchase',
      },
      {
        title: 'Proveedores',
        to: { name: 'suppliers' },
        action: 'manage',
        subject: 'Supplier',
      },
      {
        title: 'Categorías y Marcas',
        to: { name: 'categories' },
        action: 'manage',
        subject: 'Category',
      },
    ],
  },
  {
    title: 'Reportes y Finanzas',
    icon: { icon: 'ri-pie-chart-2-line' },
    children: [
      {
        title: 'Kardex de Inventario',
        to: { name: 'reports-kardex' },
        action: 'read',
        subject: 'Product',
      },
      {
        title: 'Rentabilidad y Utilidades',
        to: { name: 'reports-profitability' },
        action: 'manage',
        subject: 'all',
      },
      {
        title: 'Valoración Patrimonial',
        to: { name: 'reports-inventory-valuation' },
        action: 'manage',
        subject: 'all',
      },
    ],
  },
  {
    title: 'Administración',
    icon: { icon: 'ri-settings-4-line' },
    action: 'manage',
    subject: 'all',
    children: [
      {
        title: 'Personal / Usuarios',
        to: { name: 'users' },
        action: 'manage',
        subject: 'User',
      },
      {
        title: 'Formas de Pago',
        to: { name: 'payment-methods' },
        action: 'manage',
        subject: 'PaymentMethod',
      },
      {
        title: 'Mi Empresa',
        to: { name: 'settings-company' },
        action: 'manage',
        subject: 'all',
      },
      {
        title: 'Auditoría de Accesos',
        to: { name: 'audit-logins' },
        action: 'manage',
        subject: 'User',
      },
    ],
  },
]
