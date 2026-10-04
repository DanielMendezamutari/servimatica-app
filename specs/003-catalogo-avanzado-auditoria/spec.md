# Especificación: Capítulo 3 — Catálogo Jerárquico Avanzado, Marcas, Condición de Producto y Auditoría de Accesos

**Feature Branch**: `003-catalogo-avanzado-auditoria`
**Created**: 2026-10-03
**Status**: Draft
**Input**: Solicitud de usuario para enriquecer el catálogo comercial de computación con clasificación jerárquica (Categoría / Familia / Subfamilia), Marcas y Modelos, ficha avanzada de producto con Condición comercial (Nuevo, Seminuevo/Open Box, Usado, Reacondicionado), exportación del inventario a Excel, registro de auditoría de inicios de sesión y exportación de nómina de usuarios.

---

## 1. Objetivo y Contexto de Negocio

Servimática opera en el rubro de comercialización y servicio técnico de computadoras, partes, periféricos y accesorios. A medida que el catálogo de productos crece y el personal opera en tienda física y canales digitales, surgen tres necesidades operativas críticas:

1. **Clasificación granular de inventario**: En computación, agrupar productos en una sola categoría plana es insuficiente (por ejemplo, "Componentes" abarca cosas tan distintas como procesadores, memorias RAM, tarjetas gráficas y fuentes de poder). Se requiere una estructura jerárquica clara (Categoría principal -> Familia / Subfamilia) que agilice la búsqueda y la organización.
2. **Marcas, Modelos y Condición del Producto**: Los clientes buscan productos por fabricante específico (ASUS, HP, Dell, Lenovo, Logitech, Kingston) y modelo exacto. Asimismo, el mercado boliviano de computadoras maneja tanto equipos nuevos de paquete como equipos seminuevos (Open Box), usados y reacondicionados (refurbished). Registrar con precisión la condición del equipo evita reclamos de garantía y fija expectativas claras de compra.
3. **Auditoría de personal y reportes operativos**: Para la seguridad del negocio, el Dueño necesita conocer con exactitud quién ingresa al sistema y cuándo (historial de accesos / inicios de sesión), así como la capacidad de descargar en hojas de cálculo (Excel) tanto la nómina de usuarios como el inventario general con sus respectivos precios en Bolivianos (Bs.).

---

## Clarifications

### Session 2026-10-03
- Q: ¿Deseas incorporar en este capítulo los campos detallados de la ficha de usuario (Documento/CI, Teléfono, Dirección, Sexo, Foto de perfil y Comisión de ventas) mostrados en tu imagen de referencia? → A: Sí (Opción A), con distinción estricta entre campos obligatorios (N° de Documento/CI, Nombres y Apellidos, Usuario de acceso, Contraseña de acceso, Nivel/Rol y Status de acceso) y campos opcionales (Teléfono, Dirección domiciliaria, Sexo, Correo, Comisión de ventas % [predeterminada en 0%] y Fotografía de perfil con avatar por defecto). Sucursal asignada por defecto a Tienda Principal / Casa Matriz.
- Q: Al registrar un producto nuevo, ¿cómo debe gestionarse la selección del Modelo tras elegir la Marca? → A: Opción A — Selector con autocompletado y creación rápida; permite elegir entre los modelos registrados de la marca seleccionada o tipear uno nuevo que se registra y vincula a dicha marca al instante sin abandonar la ficha del producto.

---

## User Scenarios & Testing *(mandatory)*


### User Story 1 - Estructura Jerárquica de Categorías y Subfamilias (Priority: P1)

Como **Dueño de la tienda**,
quiero organizar mi catálogo en categorías principales y subfamilias/subcategorías dependientes,
para clasificar con precisión repuestos, componentes y equipos, y facilitar que el vendedor encuentre artículos específicos al atender clientes.

**Why this priority**: Es la base organizativa indispensable para clasificar adecuadamente cientos de partes y piezas de computadoras. Todo el catálogo posterior depende de esta taxonomía.

**Independent Test**: Puede probarse de forma independiente creando una categoría principal (ej. "Componentes"), agregándole dos subfamilias ("Procesadores" y "Tarjetas de Video"), y verificando que el listado represente visualmente la jerarquía padre-hijo y permita filtrar por cada nivel.

**Acceptance Scenarios**:

1. **Dado** que el Dueño está en el módulo de categorías, **cuando** crea una categoría principal ingresando nombre y marcándola como raíz (sin padre), **entonces** la categoría se guarda como categoría de primer nivel.
2. **Dado** que existe una categoría principal (ej. "Componentes"), **cuando** el Dueño crea una nueva categoría y selecciona dicha categoría como padre/familia superior, **entonces** se registra como subfamilia vinculada.
3. **Dado** que existen categorías con subfamilias, **cuando** el Vendedor o Dueño consulta el filtro de categorías en el catálogo, **entonces** las subfamilias se muestran indentadas o agrupadas bajo su categoría principal correspondiente.
4. **Dado** que una subfamilia tiene productos vinculados, **cuando** el Dueño intenta eliminar la categoría padre o la subfamilia, **entonces** el sistema bloquea la eliminación informando cuántos elementos dependen de ella antes de permitir su borrado.

---

### User Story 2 - Gestión de Marcas y Modelos de Hardware (Priority: P2)

Como **Dueño de la tienda**,
quiero registrar y administrar las marcas comerciales y sus modelos asociados,
para estandarizar los datos del catálogo, evitar nombres duplicados o mal escritos, y permitir búsquedas exactas por fabricante.

**Why this priority**: En hardware informático, la marca y el modelo definen la compatibilidad, el rendimiento y la garantía de cada producto. Centralizar este catálogo elimina la inconsistencia de datos generada por ingreso manual libre.

**Independent Test**: Puede probarse creando la marca "ASUS" y modelos como "TUF Gaming F15" o "ROG Strix B550", y verificando que en el formulario de creación de productos aparezcan disponibles para su selección inmediata.

**Acceptance Scenarios**:

1. **Dado** que el Dueño accede a la sección de Marcas, **cuando** registra una nueva marca con su nombre (ej. "Logitech") y estado activo, **entonces** la marca queda guardada y lista para usarse en productos.
2. **Dado** que existe una marca registrada, **cuando** el Dueño registra un modelo asociado a esa marca (ej. "G502 HERO"), **entonces** el modelo queda vinculado exclusivamente a dicha marca.
3. **Dado** que una marca tiene productos registrados en catálogo, **cuando** el Dueño la desactiva, **entonces** la marca no se ofrece para nuevos productos pero los productos existentes conservan su información histórica.
4. **Dado** que el Dueño intenta registrar una marca con un nombre ya existente (insensible a mayúsculas/minúsculas), **entonces** el sistema rechaza la operación y muestra un aviso de duplicidad.
5. **Dado** que el Dueño está en el formulario de producto y selecciona una marca (ej. "Kingston"), **cuando** el modelo deseado (ej. "Fury Beast DDR5") no existe en la lista y el usuario lo tipea, **entonces** el selector ofrece crearlo al vuelo vinculándolo de inmediato a dicha marca sin abandonar la edición del producto.

---

### User Story 3 - Ficha de Producto Avanzada con Condición Comercial y Exportación de Inventario (Priority: P3)

Como **Dueño o Vendedor**,
quiero registrar y visualizar productos asociando categoría/subfamilia, marca, modelo y su condición física (Nuevo, Seminuevo/Open Box, Usado, Reacondicionado), y exportar el inventario completo a Excel,
para mantener un control comercial estricto y disponer de planillas de cálculo para auditoría y listas de precios físicas.

**Why this priority**: Brinda el detalle técnico y comercial necesario para vender computadoras de forma profesional y transparente, permitiendo exportar la información del negocio a planillas externas para inventarios físicos.

**Independent Test**: Puede probarse creando un producto seleccionando categoría, subfamilia, marca, modelo y condición "Seminuevo / Open Box" con precio en Bs., y posteriormente pulsando "Exportar a Excel" para verificar que el archivo descargado contenga todos los campos correctos (con costos visibles solo para el Dueño).

**Acceptance Scenarios**:

1. **Dado** que el Dueño completa la ficha de un nuevo producto, **cuando** selecciona Categoría, Subfamilia, Marca, Modelo y Condición ("Nuevo", "Seminuevo / Open Box", "Usado", "Reacondicionado"), **entonces** el producto se guarda con toda su ficha técnica consolidada.
2. **Dado** que el Vendedor consulta el catálogo, **cuando** revisa la lista de productos, **entonces** puede ver de forma destacada (mediante etiquetas visuales) la condición de cada artículo y su precio de venta en Bolivianos (Bs.), sin acceso alguno al costo de compra ni margen de ganancia.
3. **Dado** que el Dueño presiona el botón "Exportar a Excel" en la vista de inventario, **cuando** se genera la descarga, **entonces** obtiene un archivo en formato `.xlsx` con todas las columnas del catálogo (SKU, Nombre, Categoría, Subfamilia, Marca, Modelo, Condición, Costo de compra en Bs., Precio de venta en Bs., Margen, Stock actual, Stock mínimo y Estado).
4. **Dado** que un Vendedor solicita la exportación de la lista de precios a Excel, **cuando** descarga el archivo, **entonces** el reporte omite estrictamente los costos de compra y los márgenes, incluyendo únicamente precios de venta al público y existencias en stock.

---

### User Story 4 - Registro de Auditoría de Inicios de Sesión y Exportación de Personal (Priority: P4)

Como **Dueño de la tienda**,
quiero consultar un historial detallado de accesos e inicios de sesión del sistema y poder exportar la lista de usuarios a Excel,
para auditar la seguridad operativa, saber qué personal ingresó, desde qué dispositivo y en qué fecha/hora, y contar con una nómina administrativa respaldada.

**Why this priority**: Garantiza la seguridad del sistema y la trazabilidad de accesos conforme al Principio VI de la Constitución (Privacidad y Seguridad).

**Independent Test**: Puede probarse iniciando sesión con un usuario válido y con uno erróneo, ingresando como Dueño al módulo de Auditoría de Accesos para verificar que ambos intentos quedaron registrados cronológicamente con su fecha, usuario, dirección IP, navegador y resultado (exitoso/fallido), y luego descargando la nómina de usuarios a Excel desde el módulo de personal.

**Acceptance Scenarios**:

1. **Dado** que el Dueño abre el formulario de gestión de usuario, **cuando** ingresa los campos obligatorios (N° de Documento/CI, Nombres y Apellidos, Usuario de Acceso, Contraseña, Rol y Estado) y opcionalmente completa Teléfono, Dirección, Sexo, Correo, Comisión de ventas o Fotografía, **entonces** el usuario se crea exitosamente; si falta algún campo obligatorio, el sistema resalta el error impidiendo el guardado.
2. **Dado** que un usuario ingresa credenciales en la pantalla de acceso, **cuando** el inicio de sesión es exitoso o fallido, **entonces** el sistema registra de forma automática un evento de auditoría con: fecha y hora exacta, nombre de usuario ingresado, dirección IP, información del navegador/dispositivo y estado del intento (Exitoso / Contraseña incorrecta / Usuario inexistente).
3. **Dado** que el Dueño entra al panel de Auditoría de Accesos, **cuando** consulta el registro, **entonces** visualiza una tabla ordenada cronológicamente (más recientes primero) con filtros por rango de fechas, usuario y resultado.
4. **Dado** que el Dueño está en el módulo de Usuarios, **cuando** hace clic en "Exportar a Excel", **entonces** se descarga una planilla `.xlsx` con la nómina completa del personal (Documento de Identidad/CI, Nombres y Apellidos, Sexo, Teléfono, Dirección, Correo electrónico, Usuario de acceso, Rol asignado, Estado, Comisión por ventas %, Fecha de registro y Último acceso registrado).

---

### Edge Cases

- **¿Qué ocurre si se elimina una categoría padre que contiene subfamilias?** El sistema no permite el borrado directo. Debe exigir que primero se reasignen o eliminen las subfamilias dependientes para evitar dejar subcategorías huérfanas.
- **¿Qué sucede al exportar a Excel con filtros activos en la tabla?** La exportación debe respetar los filtros aplicados en pantalla (por ejemplo, exportar únicamente los productos de condición "Usado" o de la marca "Lenovo" si el usuario tiene ese filtro activo). Si no hay filtros aplicados, exporta la totalidad del catálogo.
- **¿Cómo se maneja un producto de marca o modelo genérico?** Se permite registrar o seleccionar una marca/modelo "Genérico" o "Sin Marca", y también se permite que el campo Modelo sea opcional para accesorios sencillos (como cables HDMI o fundas) que no poseen número de modelo comercial.
- **¿Qué pasa si se registran múltiples intentos fallidos de inicio de sesión de un usuario?** Cada intento fallido queda asentado en el log de auditoría con su respectivo motivo de rechazo y marca temporal, permitiendo al Dueño detectar posibles intentos de vulneración de cuentas.
- **¿Qué sucede si no se proporciona fotografía o comisión de ventas al crear un usuario?** Al ser campos opcionales, la comisión se inicializa en 0.00% y para la fotografía se muestra un avatar con las iniciales del nombre o imagen neutra predeterminada.

---

## Requirements *(mandatory)*

### Functional Requirements

- **RF-026**: El sistema DEBE permitir estructurar las categorías en dos niveles jerárquicos: Categoría Principal (Familia) y Subcategoría (Subfamilia).
- **RF-027**: El sistema DEBE impedir la eliminación de una categoría que contenga subcategorías hijas o productos asignados, requiriendo reasignación o desvinculación previa.
- **RF-028**: El sistema DEBE ofrecer un módulo de administración de Marcas comerciales, permitiendo crear, editar, activar/desactivar y listar fabricantes con nombres únicos.
- **RF-029**: El sistema DEBE permitir registrar Modelos vinculados a una Marca específica, tanto desde el módulo de Marcas/Modelos como mediante creación rápida al vuelo desde el formulario de producto cuando se introduce un nombre de modelo no existente.
- **RF-030**: El sistema DEBE permitir que el campo Modelo sea opcional al registrar un producto, para dar soporte a accesorios o insumos que no posean modelo formal.
- **RF-031**: El sistema DEBE enriquecer la ficha del producto permitiendo seleccionar: Categoría Principal, Subfamilia/Subcategoría (filtrada dinámicamente según la categoría principal seleccionada), Marca comercial y Modelo.
- **RF-032**: El sistema DEBE incorporar en cada producto el campo obligatorio "Condición del producto", con las opciones exactas: `Nuevo`, `Seminuevo / Open Box`, `Usado`, `Reacondicionado`.
- **RF-033**: El sistema DEBE mostrar indicadores visuales (badges de colores distintivos) para la condición del producto en las listas y vistas detalladas de productos.
- **RF-034**: El sistema DEBE permitir al Dueño exportar el catálogo completo o filtrado de inventario a formato de hoja de cálculo estándar `.xlsx`, incluyendo: SKU, Nombre, Categoría, Subcategoría, Marca, Modelo, Condición, Costo de compra (Bs.), Precio de venta (Bs.), Margen (%) y en Bs., Stock actual, Stock mínimo y Estado.
- **RF-035**: El sistema DEBE permitir la exportación de lista de precios a Excel para el rol Vendedor, garantizando que el archivo resultante NO contenga costos de compra, márgenes de ganancia ni información confidencial de proveedores.
- **RF-036**: El sistema DEBE registrar de manera automática e inmutable cada intento de inicio de sesión (autenticación) con: fecha y hora exacta, identificador/usuario ingresado, dirección IP del cliente, agente de usuario (navegador/sistema operativo) y resultado (Éxito o Fallo con motivo resumido).
- **RF-037**: El sistema DEBE proveer al Dueño una vista dedicada de "Historial de Inicios de Sesión" (Auditoría de Accesos) con paginación y filtros por: rango de fechas, usuario y resultado del intento (Exitoso / Fallido).
- **RF-038**: El sistema DEBE permitir al Dueño exportar la nómina completa de usuarios del sistema a formato `.xlsx` con todas sus columnas de perfil (Documento/CI, Nombres y Apellidos, Sexo, Teléfono, Dirección, Correo, Usuario, Rol, Estado, Comisión %, Fecha de creación y Fecha del último inicio de sesión).
- **RF-039**: El sistema DEBE admitir la búsqueda y filtrado de productos en el catálogo web por: Marca, Condición comercial y Subfamilia, además de los filtros existentes de texto y categoría.
- **RF-040**: El sistema DEBE garantizar que todos los valores monetarios exportados en los reportes de Excel se presenten en Bolivianos (`Bs.`), conservando 2 decimales numéricos.
- **RF-041**: El sistema DEBE estructurar el registro y edición de usuarios distinguiendo estrictamente campos OBLIGATORIOS (N° de Documento/CI, Nombres y Apellidos, Usuario de Acceso, Contraseña al crear, Rol y Estado) y campos OPCIONALES (N° de Teléfono, Dirección Domiciliaria, Sexo, Correo Electrónico, Comisión por Ventas % y Fotografía de perfil).
- **RF-042**: El sistema DEBE permitir subir una fotografía de usuario opcional (formatos jpg/png, peso máximo configurable de hasta 2MB), mostrando iniciales o avatar neutro predeterminado cuando no se adjunte archivo.

---

### Key Entities *(include if feature involves data)*

- **Categoría Jerárquica (Category)**: Unidad de clasificación taxonómica. Posee nombre único, descripción opcional, identificador de categoría superior opcional (`parent_id`) para definir jerarquía padre-hijo, y estado activo/inactivo.
- **Marca (Brand)**: Fabricante comercial del producto (ej. ASUS, Kingston, Logitech). Posee nombre único y estado activo/inactivo.
- **Modelo (ProductModel)**: Referencia o serie específica dentro de una marca (ej. TUF Gaming, Fury Beast, G305). Posee nombre, relación con su Marca padre y estado activo/inactivo.
- **Producto con Ficha Avanzada (Product)**: Extensión del artículo de inventario. Incluye relaciones con Categoría, Subcategoría, Marca y Modelo, campo de Condición comercial (`nuevo`, `open_box`, `usado`, `reacondicionado`), especificaciones breves opcionales, además de los campos base de SKU, precios en Bs., stock actual y stock mínimo.
- **Usuario con Ficha Completa (User)**: Personal operativo y administrativo. Atributos obligatorios: N° de documento (CI), nombres y apellidos, usuario de acceso (único), contraseña cifrada, rol (administrador/vendedor), estado (activo/inactivo). Atributos opcionales: teléfono, dirección domiciliaria, sexo, correo electrónico, comisión por ventas (%) y ruta de foto de perfil.
- **Registro de Acceso (LoginLog)**: Registro inmutable de seguridad. Posee identificador de usuario (o nombre intentado si falló), marca temporal exacta, dirección IP, información de cabecera de navegador/dispositivo y estado de la autenticación (`success`, `failed_credentials`, `failed_inactive_user`).


---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-012**: El Dueño puede configurar una jerarquía de categoría y subfamilia completa en menos de 1 minuto desde la interfaz web.
- **SC-013**: El selector de subfamilias y modelos en el formulario de creación de productos actualiza sus opciones en menos de 1 segundo al cambiar de categoría o marca.
- **SC-014**: El 100% de los intentos de inicio de sesión (tanto exitosos como fallidos) quedan registrados de manera verificable en la vista de auditoría en tiempo real.
- **SC-015**: La descarga de las planillas de inventario y de usuarios en formato Excel (.xlsx) se completa en menos de 5 segundos para catálogos de hasta 1,000 registros.
- **SC-016**: La planilla de Excel descargada por un usuario con rol Vendedor contiene cero (0) menciones a costos de compra, utilidades o márgenes comerciales.
- **SC-017**: Los clientes y vendedores pueden identificar visualmente en la tabla la condición de un producto (Nuevo, Seminuevo/Open Box, Usado, Reacondicionado) en menos de 2 segundos mediante colores de etiquetas distintivos.
- **SC-018**: Un usuario no técnico puede verificar la nómina exportada de usuarios y el historial de accesos abriendo el archivo descargado en cualquier software estándar de hojas de cálculo sin errores de formato de caracteres ni de números.

---

## Assumptions

- Se mantiene la compatibilidad con los productos y categorías ya registrados en la base de datos existente del Capítulo 2 (los productos existentes asumirán la condición por defecto de "Nuevo" y mantendrán su categoría asignada como principal).
- La descarga de Excel se realiza de manera nativa generando archivos compatibles con Microsoft Excel, LibreOffice Calc y Google Sheets.
- La captura de dirección IP y agente de usuario para la auditoría de accesos respeta los encabezados estándar de solicitudes HTTP del cliente.
- En este capítulo la exportación a Excel cubre Inventario y Usuarios; las exportaciones de ventas y proformas se integrarán en sus respectivos capítulos de transacciones.
- La interfaz gráfica mantiene la coherencia visual con la plantilla base de `admin-full-version/` utilizando componentes preexistentes de Vuetify y CASL para control de permisos.
