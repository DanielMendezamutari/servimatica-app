# Feature Specification: Gestión Centralizada de Formas de Pago, Cuentas Bancarias y Experiencia en Filtros de Transacciones

**Feature Branch**: `006-gestion-formas-pago`  
**Created**: 2026-10-03  
**Status**: Draft  
**Input**: User description: "aqui en desde asta deberia tambien mejorar la experiencia de usuario /speckit-specify y especidica lo otro que me dices (gestión centralizada de formas de pago para todo el sistema, POS, compras y conciliación)"

---

## 1. Visión General del Capítulo

Servimática opera en el mercado boliviano combinando atención física en tienda con canales digitales (WhatsApp y redes). Actualmente, los métodos de pago están fijados de manera estática mediante cadenas de texto rígidas (`efectivo`, `qr`, `transferencia`), lo que impide al Dueño:
1. Configurar sus cuentas bancarias reales (ej. *Banco Unión*, *Banco Mercantil Santa Cruz*, *BCP*, *Banco FIE*, *Tigo Money*, *Yape Bolivia*).
2. Subir o actualizar la imagen de su **código QR de cobro oficial (Simple QR)** para que el cajero lo proyecte directamente al cliente en pantalla o tablet durante la venta.
3. Exigir el registro obligatorio del **Número de Referencia / Comprobante** en transferencias y QRs para evitar fraudes por comprobantes falsos.
4. Desglosar con exactitud en el **Arqueo y Cierre de Caja Diaria** cuánto dinero ingresó físicamente a la gaveta de billetes y cuánto ingresó a cada cuenta bancaria o billetera digital.
5. Disfrutar de una experiencia fluida y moderna al filtrar compras, ventas y auditorías por rangos de fecha (`Desde` / `Hasta`) con selector de calendario integrado y accesos rápidos de tiempo (`Hoy`, `7 días`, `Este Mes`).

Este capítulo resuelve de punta a punta la administración de métodos de pago y cuentas bancarias, conectándolos de manera transparente y desacoplada con el Punto de Venta (POS), Compras a Proveedores, Proformas y Arqueo de Caja Chica.

---

## 2. Historias de Usuario y Criterios de Aceptación (User Stories)

### User Story 1 - Catálogo y Parametrización de Formas de Pago por el Dueño (Priority: P1)

Como Dueño de Servimática,  
quiero acceder a un panel exclusivo de "Formas de Pago" en el sistema para dar de alta, editar y habilitar o deshabilitar cuentas bancarias, billeteras móviles y cobros en efectivo,  
para que el negocio tenga control formal y flexible de todos sus canales de recaudación y egreso de dinero.

**Why this priority**: Es la base de datos estructural y maestra requerida antes de poder utilizar métodos dinámicos en ventas y compras.

**Independent Test**: El Dueño ingresa a `Ajustes > Formas de Pago`, registra una cuenta *"Banco Unión - QR Simple"* adjuntando la imagen del código QR y marcando *"Requiere N° de Referencia"*. Luego verifica que aparezca activa en el listado y que los empleados vendedores no tengan permisos para alterar la configuración.

**Acceptance Scenarios**:
1. **Dado** que el usuario tiene rol `dueño`, **cuando** accede a `/payment-methods`, **entonces** visualiza la tabla de métodos de pago registrados con su nombre, tipo, titular/cuenta, estado (activo/inactivo) y ámbito (`ventas`, `compras`, `ambos`).
2. **Dado** que el Dueño crea o edita un método de pago, **cuando** selecciona tipo `qr` o `transferencia`, **entonces** puede ingresar banco emisor, número de cuenta, nombre del titular y cargar una imagen del código QR oficial (`.png` o `.jpg`).
3. **Dado** que un método de pago tiene el flag `requires_reference` en `true`, **cuando** un cajero cobre una venta usándolo, **entonces** el sistema le exigirá obligatoriamente el número de transacción bancaria.
4. **Dado** que una forma de pago ya tiene ventas o compras históricas registradas, **cuando** el Dueño decide darla de baja, **entonces** el sistema no la elimina físicamente de la base de datos sino que desactiva su estado (`is_active = false`), impidiendo su selección en nuevas operaciones pero preservando la integridad de los reportes pasados.
5. **Dado** que un usuario con rol `vendedor` intenta consultar o modificar las rutas de configuración de formas de pago, **cuando** realiza la petición, **entonces** el sistema responde con error `403 Forbidden` respaldado por CASL y middleware de backend.

---

### User Story 2 - Cobro Dinámico en Punto de Venta (POS) con Despliegue de QR Oficial (Priority: P2)

Como cajero o vendedor en mostrador,  
quiero que al presionar "Cobrar" en el POS pueda elegir entre las formas de pago activas de la tienda y ver en pantalla el QR oficial y los datos bancarios del negocio,  
para cobrar con rapidez y certeza sin tener que buscar físicamente cartones impresos o tarjetas de cuentas.

**Why this priority**: Es el punto de interacción más crítico del negocio físico con los clientes. Optimiza el tiempo de atención y asegura la captura del comprobante bancario.

**Independent Test**: Agregar un producto al carrito en el POS, presionar "Cobrar", seleccionar *"QR Banco Unión"*, verificar que en la pantalla se renderice el código QR oficial de la tienda junto con el monto exacto en Bolivianos (`Bs.`), ingresar el número de referencia del comprobante y confirmar la venta exitosamente.

**Acceptance Scenarios**:
1. **Dado** que el cajero abre el modal de cobro (`CheckoutDialog.vue`), **cuando** se renderizan los métodos disponibles, **entonces** el sistema muestra dinámicamente las formas de pago activas configuradas para `ventas` o `ambos`.
2. **Dado** que el cajero selecciona un método de tipo `qr`, **cuando** la forma de pago tiene una imagen cargada, **entonces** se visualiza el QR en alta resolución en el centro del modal con la leyenda indicando al cliente: *"Escanee el código QR para transferir Bs. [Total]"*.
3. **Dado** que el cajero selecciona una transferencia bancaria, **cuando** se visualiza la instrucción, **entonces** se presentan los datos institucionales: Banco, Número de Cuenta, Titular y NIT de Servimática.
4. **Dado** que el método seleccionado exige referencia bancaria, **cuando** el cajero intenta confirmar la venta dejando el campo vacío, **entonces** el botón se mantiene deshabilitado o muestra validación de campo requerido.
5. **Dado** que se confirma la venta, **cuando** se almacena en base de datos, **entonces** la venta guarda la clave foránea `payment_method_id`, el nombre congelado del método y el código de comprobante para auditoría.

---

### User Story 3 - Liquidación Dinámica en Compras y Recepciones a Proveedores (Priority: P3)

Como Dueño de Servimática,  
quiero seleccionar la cuenta bancaria o forma de pago exacta al recepcionar compras mayoristas de mercadería,  
para saber con exactitud de qué cuenta corporativa salió el dinero para pagar al distribuidor.

**Why this priority**: Completa el ciclo de egresos y evita confusiones sobre si una compra mayorista se pagó en efectivo de caja o por transferencia desde una cuenta bancaria específica.

**Independent Test**: Ingresar a `/purchases/create`, seleccionar proveedor, agregar ítems y en la sección *"Método de Liquidación"* seleccionar *"Transferencia Banco Mercantil Santa Cruz"*, confirmando el registro con su comprobante de débito.

**Acceptance Scenarios**:
1. **Dado** que el Dueño registra una nueva compra, **cuando** despliega el selector *"Método de Liquidación"*, **entonces** el listado carga dinámicamente las formas de pago activas asignadas a `purchases` o `ambos`.
2. **Dado** que la condición de compra es `contado` y se paga por transferencia, **cuando** se guarda la compra, **entonces** el registro asocia el `payment_method_id` correspondiente.
3. **Dado** que la condición es `credito`, **cuando** se pague una cuota o cancelación futura, **entonces** se podrá seleccionar la forma de pago mediante la cual se liquida la obligación.

---

### User Story 4 - Conciliación Detallada en el Arqueo y Cierre de Caja Diaria (Priority: P4)

Como cajero o Dueño,  
quiero que al cerrar el turno de caja el sistema me presente un resumen discriminado por cada forma de pago (Efectivo físico esperado vs. Total QR vs. Total Transferencias),  
para que el arqueo de billetes cuadre con precisión y se verifiquen los extractos bancarios del día sin mezclar los fondos.

**Why this priority**: Garantiza la transparencia financiera y previene fugas de dinero o confusiones entre lo que debe estar en el cajón y lo que ingresó a los bancos.

**Independent Test**: Abrir caja con `Bs. 100,00`, realizar una venta en efectivo de `Bs. 200,00` y dos ventas por QR sumando `Bs. 500,00`. Al cerrar caja, el resumen debe indicar explícitamente: *Efectivo en Caja Esperado: Bs. 300,00*, *Recaudación Digital QR: Bs. 500,00 (en cuenta)*.

**Acceptance Scenarios**:
1. **Dado** que un usuario solicita el cierre de caja (`CloseCashShiftDialog.vue`), **cuando** se consulta el balance del turno, **entonces** la API calcula y devuelve la sumatoria desglosada por cada forma de pago utilizada durante el turno.
2. **Dado** que se realiza el conteo físico, **cuando** el cajero introduce el monto en efectivo, **entonces** el cálculo de diferencia (sobrante/faltante) se aplica **exclusivamente** sobre el efectivo físico esperado, sin alterar los cobros que fueron acreditados en cuentas bancarias.
3. **Dado** que se imprime el ticket de cierre de caja, **cuando** se genera el documento, **entonces** incluye la tabla resumen con los totales recaudados por cada método y banco.

---

### User Story 5 - Experiencia de Usuario Unificada en Filtros Temporales (Priority: P5)

Como usuario del panel administrativo (Dueño o Vendedor),  
quiero filtrar compras, ventas, auditorías y reportes mediante selectores de fecha modernos con calendario visual emergente y botones de rango rápido (`Hoy`, `7 días`, `Este Mes`),  
para no lidiar con campos de texto manuales incómodos ni formatos confusos de fecha.

**Why this priority**: Resuelve la fricción reportada directamente por el usuario en la interfaz visual, elevando el estándar de calidad de toda la aplicación.

**Independent Test**: Acceder a `/purchases` o `/sales`, hacer clic en el botón de acceso rápido *"Este Mes"*, verificar que los campos *"Desde"* y *"Hasta"* se autocompleten con el primer y último día del mes en curso y que la tabla recargue los registros instantáneamente sin recargar la página.

**Acceptance Scenarios**:
1. **Dado** que un usuario accede a las pantallas de listado con filtros de fecha (`/purchases`, `/sales`, `/audit/logins`), **cuando** interactúa con los campos *"Desde"* y *"Hasta"*, **entonces** se despliega el componente de calendario `AppDateTimePicker` con formato estándar boliviano `AAAA-MM-DD` e icono de calendario.
2. **Dado** que el usuario hace clic en los botones rápidos (`Hoy`, `7d`, `Mes`), **cuando** se activa la opción, **entonces** las fechas se calculan y actualizan en tiempo real disparando la búsqueda con debounce automático.
3. **Dado** que el usuario limpia los filtros de fecha mediante el icono de borrado (`clearable`), **cuando** los campos quedan vacíos, **entonces** la consulta restaura el listado completo sin restricciones de fecha.

---

## 3. Casos Límite y Reglas de Negocio (Edge Cases)

- **¿Qué ocurre si el Dueño desactiva una forma de pago que un cajero tiene abierta en su pantalla de cobro?**  
  Al momento de enviar la venta, el backend valida que `payment_method_id` exista y esté activo (`is_active = true`). Si fue desactivada segundos antes, la API rechaza la petición con un mensaje amigable: *"La forma de pago seleccionada ya no está disponible; por favor elija otra"*.
- **¿Qué sucede si una tienda no sube una imagen de QR para un método tipo `qr`?**  
  El sistema muestra un icono estilizado de QR con la instrucción bancaria en texto y los datos de titular/celular/cuenta para que el cliente realice el pago manual si no hay imagen gráfica cargada.
- **¿Qué ocurre si se anula una venta cobrada mediante QR o Transferencia?**  
  La anulación revierte el stock al inventario e invalida la comisión del vendedor; además, deja constancia de que la devolución al cliente debe gestionarse por transferencia bancaria inversa o nota de crédito, sin afectar el conteo de efectivo físico de la gaveta.
- **¿Cómo interactúan las fechas cuando el usuario selecciona "Desde" posterior a "Hasta"?**  
  El componente valida que la fecha de inicio no sea mayor a la de finalización; si ocurre, la fecha límite se ajusta automáticamente o se muestra un mensaje de aviso impidiendo peticiones incoherentes al servidor.

---

## 4. Requisitos del Sistema (System Requirements)

### Requisitos Funcionales
- **FR-001**: El sistema DEBE proporcionar una tabla de base de datos `payment_methods` con los campos: `id`, `name`, `type` (`cash`, `qr`, `bank_transfer`, `card`, `other`), `bank_name`, `account_number`, `account_holder`, `qr_image_path`, `requires_reference`, `applies_to` (`sales`, `purchases`, `both`), `is_active`, `sort_order`, `timestamps`.
- **FR-002**: Las tablas `sales`, `purchases` y `quotes` DEBEN permitir registrar la referencia foránea `payment_method_id` (nullable por retrocompatibilidad) y el campo `reference_number` (string nullable).
- **FR-003**: El sistema DEBE sembrar (*seed*) por defecto los métodos iniciales del negocio:
  1. *Efectivo en Mostrador* (tipo: `cash`, aplica: `both`, activo: `true`).
  2. *Pago QR Simple* (tipo: `qr`, aplica: `sales`, activo: `true`, requiere referencia: `true`).
  3. *Transferencia Bancaria* (tipo: `bank_transfer`, aplica: `both`, activo: `true`, requiere referencia: `true`).
- **FR-004**: La API DEBE proveer endpoints protegidos para Dueño en `/api/payment-methods` (GET, POST, PUT, PATCH toggle-status) y un endpoint de solo lectura para vendedores activos `/api/payment-methods/options?context=sales`.
- **FR-005**: El componente `CheckoutDialog.vue` DEBE reemplazar los botones estáticos de pago por un listado dinámico basado en las opciones provistas por el backend.
- **FR-006**: El cierre de caja (`CashShiftController::close`) DEBE agrupar las ventas del turno por método de pago y comparar la caja física únicamente contra las ventas registradas con método tipo `cash`.
- **FR-007**: Todas las vistas de historial y reportes con filtro por rango de fechas DEBEN utilizar `AppDateTimePicker` y selectores de presets rápidos, respetando el sistema de diseño Vuetify.

---

## 5. Criterios de Éxito y Verificación (Success Criteria)

- **SC-001**: El Dueño puede dar de alta un nuevo banco o QR con imagen y verlo disponible en el POS en menos de 60 segundos sin tocar código fuente ni base de datos.
- **SC-002**: Al cobrar en el POS por QR, el código oficial se visualiza nítidamente en pantalla con el monto exacto en Bolivianos (`Bs.`), permitiendo cobrar sin fricción.
- **SC-003**: El arqueo de caja refleja el 100% de coincidencia entre el efectivo contado y las ventas físicas, mostrando por separado el total recaudado en bancos.
- **SC-004**: Los filtros de fecha en Compras, Ventas y Auditorías se manipulan con un solo clic mediante presets o abriendo un calendario moderno, sin entradas de texto defectuosas.
- **SC-005**: La suite de pruebas automatizadas (`php artisan test`) y la compilación web (`pnpm run build`) se ejecutan con 100% de éxito y cero regresiones.
