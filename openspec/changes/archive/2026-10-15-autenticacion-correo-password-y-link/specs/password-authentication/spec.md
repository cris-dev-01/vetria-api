## Purpose

Permite a un usuario registrado autenticarse con su correo y su clave para obtener un token de acceso a la API.

## ADDED Requirements

### Requirement: Login con correo y clave
El sistema SHALL exponer `POST /api/login` que reciba `email` y `password` y, si las credenciales son válidas, responda con código 200 y un token de acceso de Sanctum junto con los datos básicos del usuario.

#### Scenario: Credenciales válidas
- **WHEN** se envía `POST /api/login` con el correo y la clave correctos de un usuario existente
- **THEN** la respuesta tiene código 200
- **AND** el cuerpo incluye un token de acceso y los datos del usuario (sin la clave)
- **AND** el token permite acceder a rutas protegidas con `auth:sanctum`

#### Scenario: Clave incorrecta
- **WHEN** se envía `POST /api/login` con un correo existente y una clave incorrecta
- **THEN** la respuesta tiene código 422
- **AND** no se emite ningún token

#### Scenario: Correo inexistente
- **WHEN** se envía `POST /api/login` con un correo que no pertenece a ningún usuario
- **THEN** la respuesta tiene código 422 con el mismo mensaje de error que para una clave incorrecta
- **AND** no se emite ningún token

### Requirement: Validación de los datos de login
El sistema SHALL validar que `email` sea obligatorio y tenga formato de correo, y que `password` sea obligatorio, antes de intentar autenticar.

#### Scenario: Campos faltantes o con formato inválido
- **WHEN** se envía `POST /api/login` sin `email`, sin `password` o con un `email` que no es un correo
- **THEN** la respuesta tiene código 422 con los errores de validación de cada campo afectado

### Requirement: Registro del último acceso
El sistema SHALL actualizar `last_logged_in_at` del usuario con la fecha y hora actual cada vez que se autentique correctamente con correo y clave.

#### Scenario: Login exitoso actualiza el último acceso
- **WHEN** un usuario se autentica correctamente con correo y clave
- **THEN** su campo `last_logged_in_at` queda con la fecha y hora del acceso

#### Scenario: Login fallido no modifica el último acceso
- **WHEN** un intento de login falla por credenciales inválidas
- **THEN** el campo `last_logged_in_at` del usuario no cambia

### Requirement: Límite de intentos de login
El sistema SHALL limitar la cantidad de intentos de `POST /api/login` por combinación de correo y dirección IP, y MUST responder con código 429 cuando se exceda el límite.

#### Scenario: Se excede el límite de intentos
- **WHEN** se realizan más intentos de login que el límite permitido en la ventana de tiempo para un mismo correo e IP
- **THEN** la respuesta tiene código 429
- **AND** no se evalúan las credenciales de ese intento
