# Research: Persuasión Comercial y Codificación de Cotizaciones

## 1. Problema de Caracteres Rotos () en WhatsApp

### Hallazgo:
Cuando se usa `rawurlencode` en PHP sobre strings que contienen secuencias multibyte o cuando el archivo PHP / base de datos tiene codificaciones dispares, ciertos emojis (especialmente los compuestos o modificadores como `📄`, `📅`) se descomponen en bytes huérfanos que WhatsApp Web interpreta como `\uFFFD` (carácter de reemplazo ).

### Decisión Técnica:
1. Asegurar que `WhatsAppQuoteService.php` esté guardado explícitamente en UTF-8 sin BOM.
2. Utilizar viñetas universales limpias y emojis estándar de amplio soporte garantizado:
   - `✨`, `🛡️`, `💰`, `✅`, `💳`, `📄`, `👉`, `⚡`, `📍`
3. Aplicar `rawurlencode($text)` directamente sobre una cadena UTF-8 normalizada con `mb_convert_encoding`.

---

## 2. Copywriting Persuasivo y Psicología de Compra

### Hallazgo:
Las cotizaciones tradicionales que solo enumeran productos y precios generan "ansiedad de gasto" en el cliente. En cambio, si la cotización resalta:
1. **Valor Agregado Gratis:** "Configuración inicial y programas esenciales sin costo (¡listo para usar!)".
2. **Respaldo Local:** "Garantía real con soporte técnico en nuestra tienda de Trinidad (sin enviar el equipo a otra ciudad)".
3. **Facilidad de Pago:** "Aceptamos Simple QR de cualquier banco al instante".
4. **Llamado a la Acción (CTA) de Baja Fricción:** "¿Desea que se lo reservemos para entrega hoy mismo? Solo responda a este mensaje."
La tasa de conversión y respuesta positiva aumenta notablemente.

---

## 3. Fuentes Dinámicas de Base de Datos

### Hallazgo:
Toda la información institucional debe obtenerse en tiempo real:
- `CompanySettingModel`: para nombre de tienda, slogan, dirección física y teléfonos.
- `PaymentMethodModel`: para listar las cuentas bancarias activas (ej: BNB, BCP, Banco Unión) y QR.
- `ProductModel`: para extraer `warranty_days` de cada producto cotizado y convertirlo a meses/días amigables (ej: 365 días -> "12 meses").
- `QuoteModel`: número de proforma, cliente, teléfono, asesor vendedor y fecha de vigencia.
