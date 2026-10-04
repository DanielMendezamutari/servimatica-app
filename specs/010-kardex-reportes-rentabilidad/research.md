# Research & Architecture Decisions: Kardex y Reportes Financieros

**Feature**: `010-kardex-reportes-rentabilidad`
**Date**: 2026-10-03

## 1. Algoritmo de Valuación: Costo Promedio Ponderado (CPP)

- **Decisión**: Implementar el cálculo móvil de Costo Promedio Ponderado (CPP) conforme a la legislación comercial y contable de Bolivia (art. 44 del D.S. 24051).
- **Fórmula Matemática**:
  - Saldo Inicial: $Q_0, V_0, CPP_0 = V_0 / Q_0$.
  - En cada Entrada ($q_e, c_e$):
    $$Q_{nueva} = Q_{ant} + q_e$$
    $$V_{nueva} = V_{ant} + (q_e \times c_e)$$
    $$CPP = V_{nueva} / Q_{nueva}$$
  - En cada Salida ($q_s$):
    $$c_s = CPP_{ant}$$
    $$Haber = q_s \times c_s$$
    $$Q_{nueva} = Q_{ant} - q_s$$
    $$V_{nueva} = V_{ant} - Haber$$
  - En caso de $Q_{nueva} = 0$: $V_{nueva} = 0.00$. El costo de referencia se mantiene en el último CPP hasta la siguiente compra.
- **Ajustes manuales positivos**: Adoptan el último costo de compra registrado en el catálogo (o costo base) según la clarificación resuelta en sesión `2026-10-03`.
- **Ajustes manuales negativos**: Salen al CPP vigente al momento de la merma.
- **Alternativas descartadas**:
  - PEPS / FIFO: Requiere rastreo granular de lotes específicos con fecha de vencimiento que añade complejidad innecesaria para hardware informático (viola Principio I KISS).
  - Costo de reposición: No aceptado para estados financieros oficiales en Bolivia.

---

## 2. Privacidad y Restricción de Roles (Principio VI)

- **Decisión**: Estricta separación de DTOs y autorización en dos niveles:
  1. **Backend**:
     - Endpoint `GET /api/v1/kardex/{productId}` detecta el rol del usuario autenticado vía JWT.
     - Si el rol es `dueño` o `admin`, retorna el payload completo con columnas físicas y valorizadas (`unit_cost`, `total_debit`, `total_credit`, `unit_average_cost`, `balance_value`).
     - Si el rol es `cajero` o `vendedor`, filtra automáticamente todas las propiedades de costo y valor en el Resource/DTO, retornando exclusivamente: `type`, `quantity`, `balance_quantity`, `reason`, `reference`, `date`, `user_name`.
     - Endpoints `GET /api/v1/reports/profitability` y `GET /api/v1/reports/inventory-valuation` retornan `403 Forbidden` a usuarios sin rol administrativo.
  2. **Frontend Web (CASL & Vue)**:
     - CASL subject `Report` y ability `manage`. Solo visible en el sidebar para el rol `Dueño`.
     - La vista del Kardex oculta dinámicamente las columnas de costos si `$can('manage', 'Report')` es falso.

---

## 3. Exportación a Excel / CSV

- **Decisión**: Implementar exportación a CSV con formato compatible con Excel en español (codificación UTF-8 con BOM `\uFEFF` y delimitador estándar de punto y coma `;` o coma `,`), y descarga directa en frontend/backend.
- **Justificación**:
  - Cero dependencias pesadas (KISS & YAGNI).
  - No requiere instalar librerías pesadas como `phpoffice/phpspreadsheet` en el servidor, garantizando compatibilidad inmediata en entornos compartidos y alta velocidad de procesamiento (<100ms).
  - Excel, LibreOffice y Google Sheets abren de forma nativa los archivos generados con caracteres acentuados y formato monetario en Bolivianos (`Bs.`).

---

## 4. Estructura de Persistencia e Indexación en Base de Datos

- **Decisión**: Ampliar la tabla `stock_movements` existente mediante una migración aditiva para añadir:
  - `unit_cost` decimal(12, 4) nullable
  - `total_cost` decimal(12, 2) nullable
  - `reference_type` string(50) nullable (ej. `'purchase'`, `'sale'`, `'sale_return'`, `'adjustment'`)
  - `reference_id` unsignedBigInteger nullable
- **Índices**:
  - `INDEX (product_id, created_at, id)`: optimiza la consulta secuencial del Kardex para calcular o proyectar saldos acumulados de manera ultrarrápida.
  - `INDEX (created_at, reference_type)`: optimiza los reportes de rentabilidad en rangos de fechas.
