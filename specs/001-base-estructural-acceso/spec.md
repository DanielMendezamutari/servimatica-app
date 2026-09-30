# Especificación: Capítulo 1 — Base Estructural y Acceso al Sistema v1.0

**Feature**: `001-base-estructural-acceso`  
**Created**: 2026-09-25  
**Status**: Draft  

---

## 1. Objetivo y contexto de negocio
Para que la tienda de computadoras funcione de forma organizada y segura, el sistema requiere un punto de entrada centralizado donde el Dueño y el personal de ventas puedan autenticarse de manera ágil y protegida.
Este capítulo resuelve el problema del descontrol en el acceso físico y digital: evita que personas no autorizadas manipulen la información del negocio y establece la separación obligatoria entre quien administra la tienda (Dueño) y quien atiende a los clientes (Vendedor), protegiendo la confidencialidad de los costos comerciales y garantizando que cada acción quede identificada.
Asimismo, resuelve la agilidad en mostrador permitiendo que tanto el Dueño como los Vendedores puedan ingresar no solo con su correo y contraseña estándar, sino también mediante un **código PIN rápido de 4 dígitos** junto a su usuario/correo, ideal para cambios rápidos de turno o reingresos en tienda sin necesidad de teclear contraseñas largas frente al cliente.

---

## Clarifications

### Session 2026-09-25
- Q: ¿Cuánto tiempo de inactividad debe transcurrir antes de que el sistema bloquee la pantalla y exija volver a ingresar el PIN o contraseña? → A: Duración de jornada laboral completa (8 horas); la sesión permanece activa durante todo el turno sin bloqueos forzados por inactividad, facilitando que el personal abra el sistema al llegar y trabaje de corrido, cerrándose manualmente al final del turno o tras expirar las 8 horas.
- Q: ¿Al teclear el cuarto dígito del PIN en la pantalla de login, el sistema debe autenticar e ingresar automáticamente o esperar a que el usuario presione el botón "Ingresar"? → A: Ingreso automático inmediato al digitar el 4to número del PIN (sin necesidad de hacer clic en "Ingresar").
- Q: ¿La pantalla de ingreso por PIN debe incluir un teclado numérico táctil en pantalla (botones del 0 al 9) además de permitir teclear con el teclado físico? → A: Interfaz híbrida; incluye teclado numérico visual interactivo en pantalla (botones del 0 al 9 y botón borrar) para interacción táctil y admite simultáneamente la escritura con teclado físico tradicional.
- Q: ¿Para identificarse en el login rápido por PIN, el personal debe escribir su correo electrónico completo o se debe permitir también un nombre de usuario corto (alias)? → A: Identificación flexible; el campo de identificación acepta indistintamente el correo electrónico o un nombre de usuario corto (alias/username) único registrado para el empleado (ej. "carlos").
- Q: ¿Cuáles deben ser las credenciales maestras predeterminadas para que el Dueño ingrese por primera vez tras la instalación inicial del sistema? → A: Credenciales iniciales de fábrica para el Dueño: Correo "admin@servimatica.com", alias "admin", contraseña "password" y código PIN "1234".

---

## 2. Usuarios

- **Dueño de la tienda (Administrador):**
  - *Qué sabe hacer:* Conoce los precios de compra, proveedores, márgenes y la operación total del negocio. Usa computadora y teléfono con fluidez para consultar reportes y gestionar su tienda.
  - *Qué no sabe hacer:* No es programador; no sabe de base de datos ni de comandos de consola. Necesita una interfaz visual directa e intuitiva.
- **Vendedor de salón (Usuario Operativo):**
  - *Qué sabe hacer:* Atiende clientes en mostrador, conoce características técnicas de computadoras, procesadores y periféricos. Sabe usar aplicaciones móviles y web comerciales.
  - *Qué no sabe hacer:* No gestiona usuarios del sistema, no tiene acceso a costos de compra de los mayoristas ni puede alterar la configuración de la tienda.
- **Cliente final (Receptor pasivo):**
  - No interactúa con este módulo directamente, pero se beneficia al ser atendido por un personal formalmente acreditado.

---

## 3. Escenarios de usuario (Historias de Usuario)

### HU1 — Inicio de sesión del Dueño (Prioridad: P1)
Como **Dueño de la tienda**,  
quiero ingresar al sistema introduciendo mi correo/usuario y mi contraseña (o mi código PIN de 4 dígitos),  
para acceder al panel administrativo principal y tener el control total de mi negocio.

- **Por qué esta prioridad:** Sin acceso del dueño, no existe el administrador que configure y supervise la tienda.
- **Prueba independiente:** Ingresar credenciales del dueño (por contraseña o por PIN de 4 dígitos) y verificar que se despliega el menú administrativo completo.
- **Escenarios de Aceptación:**
  1. **Dado** que el dueño está en la pantalla de inicio de sesión con la opción "Contraseña", **cuando** ingresa su correo y contraseña válida y pulsa "Ingresar", **entonces** el sistema le da acceso al panel de administración.
  2. **Dado** que el dueño selecciona la opción "Ingreso por PIN", **cuando** ingresa su correo y su código PIN de 4 dígitos correcto, **entonces** el sistema le otorga el mismo acceso total al panel de administración.
  3. **Dado** que el dueño introduce un PIN o contraseña errónea, **cuando** pulsa "Ingresar", **entonces** el sistema le niega el acceso y muestra un mensaje claro de "Credenciales incorrectas", manteniendo el correo intacto.

---

### HU2 — Gestión de personal de ventas y credenciales por el Dueño (Prioridad: P2)
Como **Dueño de la tienda**,  
quiero registrar a mis empleados asignándoles el rol de "Vendedor" con su nombre, correo, contraseña y un código PIN de 4 dígitos,  
para que cada trabajador tenga su propia cuenta de acceso independiente y pueda usar el método de entrada que le resulte más ágil.

- **Por qué esta prioridad:** Permite delegar el uso del sistema al personal de mostrador sin compartir la cuenta maestra del dueño.
- **Prueba independiente:** El dueño crea un nuevo vendedor con contraseña y PIN de 4 dígitos, y dicho vendedor puede autenticarse de inmediato con cualquiera de los dos métodos.
- **Escenarios de Aceptación:**
  1. **Dado** que el dueño está en el módulo de personal, **cuando** completa los datos de un nuevo vendedor (ej. Juan Pérez, `juan@tienda.com`, contraseña de 6 caracteres y PIN `2468`) y pulsa "Guardar", **entonces** el vendedor aparece listado en el personal activo de la tienda.
  2. **Dado** que el dueño intenta registrar a un vendedor con un correo ya existente o un PIN que no tenga exactamente 4 dígitos numéricos, **cuando** pulsa "Guardar", **entonces** el sistema le advierte del error y no guarda datos inválidos.

---

### HU3 — Inicio de sesión del Vendedor de Salón (Prioridad: P3)
Como **Vendedor de la tienda**,  
quiero iniciar sesión indicando mi correo/usuario y mi PIN de 4 dígitos (o contraseña),  
para acceder a mi espacio de trabajo de ventas de forma veloz y segura.

- **Por qué esta prioridad:** Es la puerta de entrada obligatoria para que el vendedor pueda posteriormente cotizar y consultar productos.
- **Prueba independiente:** El vendedor entra con su cuenta (vía PIN de 4 dígitos) y se comprueba que no tiene visibles los menús administrativos ni la gestión de usuarios.
- **Escenarios de Aceptación:**
  1. **Dado** que un vendedor con estado activo ingresa su correo y su PIN de 4 dígitos, **cuando** pulsa "Ingresar", **entonces** el sistema lo lleva al área operativa de ventas, mostrando su nombre en la barra superior.
  2. **Dado** que un vendedor fue marcado como "Inactivo" por el dueño, **cuando** intenta iniciar sesión (por contraseña o por PIN), **entonces** el sistema le muestra el aviso: "Su cuenta se encuentra inactiva, consulte con administración".

---

### HU4 — Cierre de sesión seguro (Prioridad: P4)
Como **Usuario autenticado (Dueño o Vendedor)**,  
quiero cerrar mi sesión activa en cualquier momento,  
para evitar que otra persona use mi cuenta si dejo el equipo o teléfono desatendido.

- **Por qué esta prioridad:** Protege la privacidad de la cuenta y evita suplantaciones entre turnos de trabajo.
- **Prueba independiente:** Pulsar "Cerrar sesión" y verificar que no se puede regresar al panel con el botón "Atrás" del navegador.
- **Escenarios de Aceptación:**
  1. **Dado** que el usuario está dentro del sistema, **cuando** pulsa "Cerrar sesión", **entonces** el sistema finaliza la sesión y lo redirige a la pantalla limpia de login.

---

## 4. Requisitos funcionales (El QUÉ observable)

- **RF-001:** El sistema debe presentar una pantalla limpia de inicio de sesión con campo para identificación (correo electrónico o nombre de usuario/alias corto) y un selector o pestaña visible para elegir el método de autenticación: `Contraseña` o `PIN de 4 dígitos`.
- **RF-002:** En el modo `PIN de 4 dígitos`, el sistema debe presentar un teclado numérico visual táctil (botones interactivos del 0 al 9 y botón borrar/limpiar) además de admitir la escritura directa desde el teclado físico tradicional. Debe autenticar y enviar automáticamente en cuanto el usuario completa el 4to dígito.
- **RF-003:** El sistema debe verificar la autenticidad de las credenciales (sea mediante contraseña o mediante PIN de 4 dígitos) y validar que el usuario esté en estado `Activo` antes de conceder acceso.
- **RF-004:** El sistema debe mostrar en todo momento en la cabecera el nombre del usuario conectado y su rol actual.
- **RF-005:** El sistema debe proveer al Dueño un panel para listar los usuarios registrados, indicando nombre, alias, correo, rol asignado y estado (Activo/Inactivo).
- **RF-006:** El sistema debe permitir al Dueño registrar y editar usuarios asignando obligatoriamente: Nombre completo, Nombre de usuario corto (alias único para acceso rápido), Correo electrónico, Contraseña (mínimo 6 caracteres), Código PIN (4 dígitos numéricos) y Rol (`Dueño` o `Vendedor`).
- **RF-007:** El sistema debe permitir al Dueño cambiar el estado de un usuario entre `Activo` e `Inactivo` con un solo clic.
- **RF-008:** El sistema debe restringir el acceso a los módulos de gestión y configuración exclusivamente al rol `Dueño/Administrador`. Si un usuario con rol `Vendedor` intenta ingresar a una zona no autorizada, el sistema debe bloquear el acceso y mostrar un mensaje de advertencia.
- **RF-009:** El sistema debe proporcionar un botón visible de "Cerrar sesión" en todas las vistas de la aplicación.

---

## 5. Reglas de negocio (Con ejemplos reales)

- **RN-001 (Separación tajante de roles):** Existen exactamente dos roles en esta versión: `Dueño` (acceso irrestricto) y `Vendedor` (acceso exclusivamente operativo).  
  *Ejemplo real:* Si el vendedor Carlos Pérez intenta acceder a la dirección de gestión de personal, el sistema le bloquea el paso y muestra el aviso "No tiene permisos para acceder a esta sección".
- **RN-002 (Requisitos de Contraseña y PIN):**  
  - Toda contraseña debe contener al menos 6 caracteres.  
  - Todo código PIN debe contener **exactamente 4 dígitos numéricos** (del `0` al `9`).  
  *Ejemplo real:* Si el dueño intenta guardar un vendedor con el PIN `123` (solo 3 dígitos) o `12AB` (con letras), el sistema rechaza el guardado indicando "El PIN debe tener exactamente 4 números".
- **RN-003 (Equivalencia de acceso):** Iniciar sesión mediante PIN de 4 dígitos otorga la misma identidad, permisos y sesión activa que ingresar con la contraseña tradicional.
- **RN-004 (Unicidad de identificación):** No pueden coexistir dos usuarios con el mismo correo electrónico ni con el mismo nombre de usuario (alias corto) en la tienda.  
  *Ejemplo real:* Si ya existe el alias `carlos` o el correo `ventas@tienda.com`, el sistema rechaza cualquier otro registro que utilice ese mismo alias o correo.
- **RN-005 (Cuenta maestra inicial garantizada):** Tras la instalación base y ejecución de migraciones/seeders, el sistema debe autogenerar una cuenta inicial con rol `Dueño/Administrador`: Correo `admin@servimatica.com`, alias `admin`, contraseña `password` y código PIN `1234`, permitiendo el ingreso inmediato para la configuración inicial y creación de personal.

---

## 6. Criterios de aceptación (Verificables con Sí/No por persona no técnica)

- **CA-001:** ¿Al ingresar correo y contraseña correctos del dueño, se accede al panel principal de la tienda en menos de 3 segundos? **[Sí / No]**
- **CA-002:** ¿Al seleccionar "Ingreso por PIN", ingresar correo y digitar el 4to dígito del PIN correcto, el sistema autentica e ingresa automáticamente a la app sin exigir clic en el botón? **[Sí / No]**
- **CA-003:** ¿Al ingresar una clave o un PIN incorrectos, el sistema permanece en el login y muestra una alerta clara de error? **[Sí / No]**
- **CA-004:** ¿El dueño puede crear un vendedor nuevo completando nombre, correo, clave de 6 caracteres y PIN de 4 dígitos? **[Sí / No]**
- **CA-005:** ¿El nuevo vendedor creado puede iniciar sesión tanto con su contraseña como con su PIN de 4 dígitos? **[Sí / No]**
- **CA-006:** ¿El vendedor carece de acceso a la pantalla de creación/gestión de usuarios? **[Sí / No]**
- **CA-007:** ¿Si el dueño cambia el estado del vendedor a "Inactivo", este ya no puede ingresar al sistema ni por clave ni por PIN? **[Sí / No]**
- **CA-008:** ¿Al pulsar "Cerrar sesión", el sistema sale inmediatamente y requiere volver a loguearse para entrar? **[Sí / No]**

---

## 7. Casos límite (Lo inesperado y errores)

- **CL-001 (Formato de PIN inválido en login):** Si en el modo PIN el usuario teclea letras o menos de 4 números, el sistema no permite pulsar "Ingresar" o advierte antes de enviar.
- **CL-002 (Campos en blanco):** Si se pulsa "Ingresar" con correo, clave o PIN vacíos, el sistema marca los campos en rojo y no permite el envío.
- **CL-003 (Intentos fallidos repetidos):** Si se ingresa un PIN o contraseña incorrecta varias veces seguidas, el sistema mantiene la seguridad con un mensaje neutro ("Credenciales no válidas").
- **CL-004 (Auto-bloqueo del Dueño):** El sistema no debe permitir que el dueño se marque a sí mismo como "Inactivo" ni que elimine su propio usuario, para evitar que la tienda quede huérfana de administrador.
- **CL-005 (Duración de sesión de jornada laboral):** La sesión del usuario permanece activa durante la jornada laboral de 8 horas sin cerrarse automáticamente por periodos cortos de inactividad, facilitando el trabajo continuo en mostrador al abrir la tienda. Si transcurren más de 8 horas continuas, el sistema expira la sesión de forma segura y solicita reautenticarse.

---

## 8. Fuera de alcance (Lo que este Capítulo 1 NO hace)

- **FA-001:** Registro público de clientes (solo el dueño crea las cuentas de su personal).
- **FA-002:** Catálogo de productos, marcas, partes de computadoras e inventario (corresponde al Capítulo 2).
- **FA-003:** Emisión de proformas, cotizaciones o ventas de mostrador (corresponde a los Capítulos 3 y 4).
- **FA-004:** Conexión con el bot de WhatsApp o TikTok live (corresponde a capítulos posteriores).
- **FA-005:** Recuperación de contraseña vía correo electrónico con servidores SMTP externos (el dueño restablece contraseñas y PINs del personal directamente desde su panel).
- **FA-006:** Registro de sucursales múltiples (en esta versión la tienda opera con una sede central en Bolivia).

---

## 9. Entidades Clave

- **Usuario:**
  - *Atributos esenciales:* Nombre completo, nombre de usuario (alias corto único), correo electrónico, contraseña, código PIN (4 dígitos), rol (`Dueño` o `Vendedor`), estado (`Activo` o `Inactivo`), fecha de creación.
- **Rol:**
  - *Atributos esenciales:* Nombre del rol (`Dueño`, `Vendedor`), nivel de privilegios en el sistema.
