# Research & Decisiones Técnicas: Capítulo 4

**Feature**: `004-ventas-proformas-pos`  
**Date**: 2026-10-03  
**Status**: Completed

Este documento analiza y resuelve las decisiones técnicas para el módulo de Punto de Venta (POS), Proformas/Cotizaciones, Control de Turnos de Caja y Comisiones de Venta en Servimática App.

---

## 1. Impresión de Recibos Térmicos (80mm/58mm) vs PDF Formal

- **Contexto**: En mostrador se requiere emitir recibos rápidos de venta para clientes casuales (impresora térmica), mientras que para Proformas a empresas o cotizaciones formales se necesita un documento PDF descargable con membrete.
- **Decisión**:
  1. **Para Venta en Mostrador (Ticket Térmico):** Se implementa un componente modal/dialog en Vue con estilos dedicados `@media print` (`@page { size: 80mm auto; margin: 0; }`). Al confirmar la venta o hacer clic en "Imprimir Ticket", se dispara la impresión nativa del navegador (`window.print()`).
     - *Ventaja:* Cero dependencias nativas o drivers de hardware propietarios; funciona instantáneamente en cualquier impresora térmica USB, Bluetooth o de red.
  2. **Para Proformas y Cotizaciones (Documento PDF Formal):** Generación en backend mediante vista Blade estructurada con membrete, logo, tabla de ítems y validez en Bolivianos, renderizada a PDF descargable y accesible vía URL directa.
- **Alternativas descartadas**:
  - Enviar comandos ESC/POS raw en binario vía WebUSB: descartado por complejidad innecesaria y problemas de compatibilidad con navegadores estándar.

---

## 2. Integración y Envío de Proformas por WhatsApp

- **Contexto**: El vendedor necesita enviar el presupuesto al cliente directamente a su número de celular boliviano (+591).
- **Decisión**:
  - Generación de enlace universal `https://wa.me/591[telefono]?text=[mensaje_url_encoded]`.
  - El mensaje incluye:
    ```text
    ¡Hola [Nombre del Cliente]! 👋
    Le compartimos la cotización de Servimática (N° PRF-000001):
    -----------------------------------
    • 1x Laptop HP 15-ef2xxx: Bs. 3.200,00
    • 1x Mouse Logitech G502: Bs. 350,00
    -----------------------------------
    💰 Total Cotizado: Bs. 3.550,00
    ⏳ Validez: 48 horas (Sujeto a disponibilidad de stock)
    📍 Visítanos en nuestra tienda física o contáctanos para apartar tu equipo.
    ```
- **Alternativas descartadas**:
  - Enviar por API no oficial de WhatsApp Web con puppeteer/headless: descartado por riesgo de baneo de la línea del negocio; el enlace nativo `wa.me` es seguro, instantáneo y controlado por el vendedor.

---

## 3. Atomicidad de Transacciones y Bloqueo de Stock

- **Contexto**: En horas pico o transmisiones en vivo (TikTok Live / mostrador), dos ventas simultáneas no deben provocar sobreventa ni dejar el stock negativo.
- **Decisión**:
  - Utilizar transacciones de base de datos (`DB::transaction`) en Laravel.
  - Bloqueo pesimista de fila (`lockForUpdate()`) al consultar y verificar existencias del producto:
    ```php
    $product = ProductModel::where('id', $item['product_id'])->lockForUpdate()->firstOrFail();
    if ($product->stock < $item['quantity']) {
        throw new InsufficientStockException("Stock insuficiente para: {$product->name}");
    }
    $product->decrement('stock', $item['quantity']);
    ```
  - Si una venta es anulada por el Dueño, se ejecuta la transacción inversa reponiendo el stock (`increment('stock', $quantity)`).

---

## 4. Control de Turnos de Caja (CashShift) y Dinero Físico

- **Contexto**: Se requiere saber cuánto dinero en efectivo ingresó a caja, evitando descuadres entre lo cobrado y lo que hay en el cajón.
- **Decisión**:
  - Tabla `cash_shifts`: cada turno pertenece a un `user_id`. Solo puede haber un turno con `status = 'open'` por usuario al mismo tiempo.
  - Apertura: pide `opening_amount` (monto inicial para cambio en `Bs.`).
  - Durante el turno:
    - Las ventas con `payment_method = 'efectivo'` suman al balance en efectivo esperado.
    - Las ventas con `payment_method = 'qr'` o `'transferencia'` se registran como cobradas pero se computan en una columna separada (`total_qr`), ya que el dinero ingresa al banco y no al cajón físico.
  - Cierre / Arqueo: el cajero ingresa el `closing_amount` (efectivo contado real). El sistema calcula:
    $$\text{diferencia} = \text{closing\_amount} - (\text{opening\_amount} + \text{ventas\_efectivo})$$
    - Si es 0: "Caja cuadrada".
    - Si es > 0: "Sobrante de Bs. X".
    - Si es < 0: "Faltante de Bs. X".

---

## 5. Cálculo y Liquidación de Comisiones

- **Contexto**: Cada vendedor tiene un porcentaje pactado (`sales_commission`, ej. 2.50%) configurado en su perfil (Capítulo 3).
- **Decisión**:
  - Al crearse la venta, se guarda de forma inmutable:
    - `commission_rate`: porcentaje del vendedor en ese momento (ej. `2.50`).
    - `commission_amount`: `(total_amount * commission_rate) / 100` redondeado a 2 decimales.
  - Si el vendedor tiene `sales_commission = 0`, la comisión es `0.00`.
  - El Dueño dispone de un reporte con filtros de fecha y vendedor para liquidar comisiones acumuladas.
