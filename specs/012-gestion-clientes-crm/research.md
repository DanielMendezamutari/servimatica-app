# Research & Architecture Decisions: Gestión Integral de Clientes (CRM)

## 1. Detección de Duplicados en Tiempo Real
- **Decisión:** Al ingresar un NIT/CI o teléfono en el formulario de registro/edición de cliente, el frontend realiza una verificación contra los registros ya cargados en memoria o vía debounce hacia el endpoint de búsqueda, mostrando una alerta contextual suave (ej. *"Ya existe un cliente registrado con este NIT: Inversiones Beni"*).
- **Razón:** Cumple con la clarificación Q3 (NIT/CI y teléfono opcionales para no frenar la venta rápida en mostrador, pero con advertencia visual para prevenir duplicidad innecesaria).

## 2. Generación de Enlace Directo a WhatsApp
- **Decisión:** Si el teléfono registrado tiene 8 dígitos y comienza con 6 o 7 (formato celular Bolivia), se formatea automáticamente con el código de país `591` para generar el enlace `https://wa.me/591XXXXXXXX`.
- **Razón:** Facilita la comunicación directa a un solo clic desde la tabla de clientes o la ficha 360°, sin que el vendedor deba tipear el número a mano en su teléfono.

## 3. Consulta Eficiente de Garantías del Cliente
- **Decisión:** En lugar de crear una tabla duplicada de garantías, se consulta la relación `SaleItem` a través de `Sale` donde `sale.client_id = $client->id`, `sale.status != 'cancelled'` y `sale_items.warranty_days > 0`.
- **Cálculo de Vigencia:**
  - `expires_at = sale.created_at + warranty_days days`.
  - `days_remaining = max(0, now()->diffInDays(expires_at, false))`.
  - Si `now() > expires_at`, la garantía se cataloga como `"Expirada"`.
- **Razón:** Reutiliza al 100% la arquitectura del Capítulo 009 sin duplicación de datos (Principio KISS/YAGNI).
