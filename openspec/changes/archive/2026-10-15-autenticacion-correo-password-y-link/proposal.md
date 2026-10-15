## Why

La API ya tiene Sanctum instalado y el modelo `User` con `HasApiTokens`, pero no existe ningún endpoint para autenticarse: `routes/api.php` solo expone `GET /user` protegido por `auth:sanctum`. Se necesita que los usuarios puedan iniciar sesión con correo y clave, y también mediante un link de un solo uso enviado a su correo, sin tener que recordar la clave.

## What Changes

- Nuevo endpoint `POST /api/login` que valida correo y clave y emite un token de Sanctum.
- Nuevo endpoint `POST /api/auth/link` que recibe un correo y envía un link de acceso. Siempre responde lo mismo, exista o no el usuario, para no revelar cuentas registradas.
- Nuevo endpoint `POST /api/auth/link/verify` que canjea el token del link por un token de Sanctum. El canje se hace por POST (no por GET) para que escáneres o previsualizadores de correo no consuman el link.
- Nueva tabla `login_links` (UUID como clave primaria) que guarda solo el hash del token y su vencimiento (15 minutos). Es una tabla temporal: el registro se elimina al canjear el link, y a lo más existe uno por usuario. Cada link es de un solo uso.
- Nuevo correo (Mailable) con el link, que apunta al frontend (`FRONTEND_URL`) y lleva el token como parámetro.
- Rate limiting en los tres endpoints.
- Al autenticarse por cualquiera de las dos vías se actualiza `users.last_logged_in_at`.
- Se acuerda usar el término "link" en código, rutas, nombres de tabla y documentación (nunca "magic-link").

No hay cambios incompatibles: no se modifica ningún endpoint existente.

## Capabilities

### New Capabilities
- `password-authentication`: inicio de sesión con correo y clave que emite un token de Sanctum.
- `link-authentication`: solicitud de un link de acceso por correo y canje del token del link por un token de Sanctum (un solo uso, vencimiento corto, sin revelar la existencia del usuario).

### Modified Capabilities
<!-- Ninguna: `openspec/specs/` aún no contiene capabilities. -->

## Impact

- **Código nuevo**: `FormRequest`, DTOs (spatie/laravel-data), Actions (lorisleiva/laravel-actions), controller de autenticación y API Resource para la respuesta, siguiendo el flujo del proyecto (Route → FormRequest → DTO → Action → Controller → Resource).
- **Base de datos**: migración de `login_links` con `uuid('id')->primary()` y `foreignUuid('user_id')`; modelo `LoginLink` con `HasUuids`.
- **Rutas**: `routes/api.php` (nuevos endpoints públicos con `throttle`).
- **Correo**: nueva clase Mailable y vista; se usa el mailer configurado en `.env` (`MAIL_MAILER=log` en desarrollo y pruebas).
- **Configuración**: uso de `FRONTEND_URL` (ya presente en `.env.example`; falta en `.env.testing`).
- **Pruebas**: tests PHPUnit de feature con `.env.testing`.
- **Dependencias**: ninguna nueva (Sanctum, laravel-data y laravel-actions ya están instalados).
- **Fuera de alcance (non-goals)**: cierre de sesión (logout), recuperación/restablecimiento de clave, registro de usuarios, verificación de correo, 2FA y autenticación SPA basada en cookies.
