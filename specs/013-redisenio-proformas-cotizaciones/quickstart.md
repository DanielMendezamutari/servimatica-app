# Quickstart: Validación de Cotizaciones y WhatsApp Comercial

## 1. Prerrequisitos
- Base de datos migrada con `company_settings` y `payment_methods`.
- Al menos un método de pago activo (QR o Banco).
- Al menos un producto registrado con días de garantía configurados.

## 2. Escenario de Validación End-to-End

### Paso 1: Generar o Consultar Cotización
Ejecutar prueba de feature:
```bash
php artisan test --filter=QuoteManagementTest
```

### Paso 2: Verificar el Mensaje Dinámico de WhatsApp
Hacer una petición autenticada al endpoint:
```bash
curl -X GET "http://servimatica-app.test/api/quotes/1/whatsapp-link" \
  -H "Authorization: Bearer {token}"
```
Verificar que la respuesta contenga:
- Saludo personalizado con el nombre del cliente.
- Nombre comercial de la empresa configurada en `company_settings`.
- Garantía explícita de cada producto (ej: `12 meses`).
- Beneficios de compra (configuración sin costo, soporte local en Trinidad).
- Datos de pago bancario / QR activos de `payment_methods`.
- Enlace directo a la proforma web: `http://servimatica-app.test/api/quotes/1/print`.
- Ausencia de caracteres rotos .

### Paso 3: Verificar la Proforma Membretada en PDF / Impresión
Abrir en el navegador:
`http://servimatica-app.test/api/quotes/1/print`
Comprobar:
- Logotipo oficial de Servimática en alta resolución.
- Columna de garantía técnica en la tabla de ítems.
- Sección de métodos de pago y cuentas bancarias al pie.
- Ajuste de estilos `@media print` para generar una hoja limpia al presionar `Ctrl + P` o `Cmd + P`.
