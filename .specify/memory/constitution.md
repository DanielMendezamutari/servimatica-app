<!--
SYNC IMPACT REPORT
- Version change: Initial template -> v1.0.0
- Ratified Date: 2026-09-25
- Last Amended: 2026-09-25
- Principles defined:
  1. Simplicidad ante todo (KISS & YAGNI)
  2. Idioma, Mercado y Moneda (Bolivia / BOB)
  3. Cero alcance fantasma (Scope Creep)
  4. Verificable por una persona no técnica
  5. Verdad única de datos en tiempo real
  6. Privacidad y seguridad de los datos del negocio
- Sections:
  - Core Principles
  - Restricciones Operativas y de Dominio
  - Flujo de Entrega y Calidad
  - Governance
-->

# Servimática App Constitution

## Core Principles

### I. Simplicidad ante todo (KISS & YAGNI)
Ante dos posibles soluciones técnicas o funcionales, se elige siempre la más simple. Es la versión 1 de la plataforma; se prohíbe cualquier tipo de complejidad o abstracción anticipada. Cada desarrollo debe resolver un problema operativo real e inmediato de la tienda de computadoras.

### II. Idioma, Mercado y Moneda (Bolivia / BOB)
Todo el sistema (panel administrativo web, app móvil para vendedores y mensajes del bot de WhatsApp) debe estar expresado en español claro y adaptado al mercado de Bolivia. La moneda oficial y predeterminada en todo el sistema es el Boliviano (código ISO: `BOB`, símbolo: `Bs.`).

### III. Cero Alcance Fantasma (Scope Creep)
Queda estrictamente prohibido implementar cualquier funcionalidad que no esté previamente descrita y aprobada en la especificación (`spec.md`) del capítulo activo. Si durante el desarrollo surge una nueva idea, mejora o requerimiento, se registra como propuesta para capítulos posteriores; nunca se construye a espaldas de la spec.

### IV. Verificable por una Persona No Técnica
Cada criterio de aceptación y de éxito debe poder comprobarse de manera visual y funcional utilizando la aplicación (navegando por la web, usando la app móvil Android o enviando un mensaje al bot de WhatsApp) en menos de dos minutos, sin necesidad de leer código fuente, examinar bases de datos o abrir terminales.

### V. Verdad Única de Datos en Tiempo Real
El panel web administrativo, la app móvil de proformas del vendedor y el bot de WhatsApp deben alimentarse de la misma y única base de datos central en tiempo real. Precios de venta, disponibilidad física y existencias de stock deben mantenerse 100% consistentes en todos los canales para evitar discrepancias con los clientes.

### VI. Privacidad y Seguridad de los Datos del Negocio
Los costos de compra a distribuidores, los márgenes de ganancia netos y los datos de proveedores son de acceso exclusivo para el rol de administrador y nunca deben exponerse en la app del vendedor ni a través del bot público de WhatsApp. Se prohíbe terminantemente alojar credenciales, API keys o secretos en el código fuente.

## Restricciones Operativas y de Dominio

- **Foco de Negocio:** Comercialización y servicio técnico de computadoras, partes, periféricos y accesorios en tienda física y canales digitales (TikTok Live + WhatsApp).
- **Consumo de Catálogo Móvil:** La app móvil nativa de los vendedores debe optimizar la velocidad de cotización y generación de proformas frente al cliente, funcionando de manera ágil y fluida.
- **Canal Automatizado (Bot WhatsApp):** El bot solo debe brindar información oficial validada contra el inventario y catálogo de precios de la base de datos, evitando respuestas inventadas o no confirmadas.

## Flujo de Entrega y Calidad

- **Desarrollo por Capítulos:** Todo avance se planifica y ejecuta mediante el flujo Spec-Driven Development (SDD: Specify -> Clarify -> Plan -> Tasks -> Implement).
- **Entrega Vertical Completa:** Ningún capítulo se considera finalizado hasta que su ciclo de backend, interfaz (web o móvil) y pruebas esté operativo y verificado por el usuario.

## Governance

Esta Constitución representa la máxima autoridad de producto y gobernanza para Servimática App y prevalece sobre cualquier otra decisión ad-hoc.
- Toda propuesta de cambio o enmienda a estos principios requiere justificación fundamentada y actualización explícita de este documento.
- El versionado sigue el estándar semántico (MAJOR para cambios de principios rectores, MINOR para ampliaciones o nuevas secciones, PATCH para ajustes de redacción).

**Version**: 1.0.0 | **Ratified**: 2026-09-25 | **Last Amended**: 2026-09-25
