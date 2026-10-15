# link-authentication Specification

## Purpose

Permite a un usuario registrado autenticarse sin clave mediante un link de un solo uso enviado a su correo, que se canjea por un token de acceso a la API.

## Requirements

### Requirement: Solicitud de link de acceso
El sistema SHALL exponer `POST /api/auth/link` que reciba un `email` y responda siempre con código 202 y el mismo mensaje, exista o no un usuario con ese correo.

#### Scenario: Correo de un usuario existente
- **WHEN** se envía `POST /api/auth/link` con el correo de un usuario existente
- **THEN** la respuesta tiene código 202 con un mensaje genérico
- **AND** se envía a ese correo un mensaje con un link de acceso

#### Scenario: Correo sin usuario asociado
- **WHEN** se envía `POST /api/auth/link` con un correo que no pertenece a ningún usuario
- **THEN** la respuesta tiene código 202 con exactamente el mismo mensaje que para un usuario existente
- **AND** no se envía ningún correo

#### Scenario: Correo con formato inválido
- **WHEN** se envía `POST /api/auth/link` sin `email` o con un valor que no es un correo
- **THEN** la respuesta tiene código 422 con el error de validación del campo

### Requirement: Contenido y vigencia del link
El link enviado por correo SHALL apuntar a la URL del frontend configurada para la aplicación y MUST incluir el token de un solo uso como parámetro. El token MUST ser aleatorio, MUST tener una vigencia de 15 minutos desde su emisión y el sistema MUST NOT almacenarlo en claro.

#### Scenario: El link apunta al frontend
- **WHEN** se envía el correo con el link de acceso
- **THEN** la URL del link pertenece al dominio del frontend configurado y contiene el token

#### Scenario: El token no se almacena en claro
- **WHEN** se genera un link de acceso
- **THEN** el valor del token enviado por correo no se encuentra almacenado tal cual en la base de datos

### Requirement: Un solo link vigente por usuario
El sistema SHALL invalidar los links anteriores aún no usados de un usuario cuando se emita uno nuevo, de modo que solo el más reciente pueda canjearse.

#### Scenario: Se solicita un segundo link
- **WHEN** un usuario solicita un nuevo link teniendo otro vigente y sin usar
- **THEN** el link anterior deja de ser válido
- **AND** el nuevo link se puede canjear con normalidad

### Requirement: Los links son registros temporales y no se acumulan
El sistema SHALL conservar un link solo mientras esté pendiente de canje. Un link MUST eliminarse al canjearse, MUST eliminarse al emitirse uno nuevo para el mismo usuario, y los links vencidos MUST eliminarse al procesarse una nueva solicitud. A cualquier usuario le SHALL corresponder como máximo un link almacenado.

#### Scenario: El canje elimina el registro
- **WHEN** un usuario canjea un link válido
- **THEN** no queda ningún link almacenado para ese usuario

#### Scenario: Una nueva solicitud reemplaza el link pendiente
- **WHEN** un usuario con un link pendiente solicita otro
- **THEN** solo existe el link nuevo para ese usuario

#### Scenario: Una nueva solicitud limpia los links vencidos
- **WHEN** se procesa una solicitud de link y existen links vencidos de otros usuarios que nunca se canjearon
- **THEN** esos links vencidos se eliminan

### Requirement: Canje del link por un token de acceso
El sistema SHALL exponer `POST /api/auth/link/verify` que reciba el `token` del link y, si es válido, responda con código 200 y un token de acceso de Sanctum junto con los datos básicos del usuario.

#### Scenario: Token válido
- **WHEN** se envía `POST /api/auth/link/verify` con un token vigente y no usado
- **THEN** la respuesta tiene código 200
- **AND** el cuerpo incluye un token de acceso y los datos del usuario (sin la clave)
- **AND** el registro del link se elimina de la base de datos

#### Scenario: Token ya usado
- **WHEN** se envía `POST /api/auth/link/verify` con un token que ya fue canjeado
- **THEN** la respuesta tiene código 422 con el mismo mensaje de error que para un token inexistente
- **AND** no se emite ningún token

#### Scenario: Token vencido
- **WHEN** se envía `POST /api/auth/link/verify` con un token cuya vigencia de 15 minutos ya terminó
- **THEN** la respuesta tiene código 422
- **AND** no se emite ningún token

#### Scenario: Token inexistente
- **WHEN** se envía `POST /api/auth/link/verify` con un token que no corresponde a ningún link emitido
- **THEN** la respuesta tiene código 422 con el mismo mensaje de error que para un token vencido o usado
- **AND** no se emite ningún token

#### Scenario: Token faltante
- **WHEN** se envía `POST /api/auth/link/verify` sin `token`
- **THEN** la respuesta tiene código 422 con el error de validación del campo

### Requirement: El canje no ocurre al abrir el link
El sistema MUST NOT autenticar ni consumir el link mediante una petición GET. Solo `POST /api/auth/link/verify` SHALL consumir el token.

#### Scenario: Petición GET al endpoint de canje
- **WHEN** se envía una petición GET a `/api/auth/link/verify`
- **THEN** la respuesta tiene código 405
- **AND** ningún link cambia de estado

### Requirement: Registro del último acceso y correo verificado
El sistema SHALL actualizar `last_logged_in_at` al canjear un link correctamente, y SHALL marcar `email_verified_at` si el usuario aún no lo tenía, dado que el acceso demuestra control sobre el correo.

#### Scenario: Canje exitoso actualiza el usuario
- **WHEN** un usuario canjea un link válido
- **THEN** su campo `last_logged_in_at` queda con la fecha y hora del canje
- **AND** si `email_verified_at` estaba vacío, queda con la fecha y hora del canje

#### Scenario: Canje fallido no modifica el usuario
- **WHEN** un canje falla por token inválido, vencido o usado
- **THEN** `last_logged_in_at` y `email_verified_at` del usuario no cambian

### Requirement: Límite de solicitudes y canjes de links
El sistema SHALL limitar la cantidad de peticiones a `POST /api/auth/link` y a `POST /api/auth/link/verify`, y MUST responder con código 429 cuando se exceda el límite.

#### Scenario: Demasiadas solicitudes de link
- **WHEN** se solicitan más links que el límite permitido en la ventana de tiempo para un mismo correo e IP
- **THEN** la respuesta tiene código 429
- **AND** no se envía ningún correo adicional

#### Scenario: Demasiados intentos de canje
- **WHEN** se realizan más intentos de canje que el límite permitido en la ventana de tiempo desde una misma IP
- **THEN** la respuesta tiene código 429
- **AND** no se evalúa el token de ese intento
