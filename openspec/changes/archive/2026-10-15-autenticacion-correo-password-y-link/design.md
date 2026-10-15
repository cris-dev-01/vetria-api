## Context

Motivación y alcance en `proposal.md`; requisitos de comportamiento en `specs/password-authentication/spec.md` y `specs/link-authentication/spec.md`.

Estado actual del repositorio:
- Sanctum 4 instalado; `User` usa `HasApiTokens`, `HasUuids` y `HasRoles`; existe `App\Models\PersonalAccessToken` con `HasUuids` registrado en `AppServiceProvider` mediante `Sanctum::usePersonalAccessTokenModel`.
- `bootstrap/app.php` ya activa `statefulApi()` y fuerza respuestas JSON para `api/*`.
- `laravel-actions` y `spatie/laravel-data` están instalados, pero aún no existen `app/Actions` ni `app/Data`; este cambio los inaugura. La arquitectura obligatoria es Route → FormRequest → DTO → Action → Controller → API Resource.
- `users` tiene `last_logged_in_at` y `email_verified_at`; la clave se hashea mediante el cast `hashed`.
- `.env.example` define `FRONTEND_URL`; `.env.testing` no. `MAIL_MAILER=log` en ambos entornos.
- No hay rate limiters definidos ni rutas de autenticación.

## Goals / Non-Goals

**Goals:**
- Dos vías de autenticación independientes que terminan en el mismo resultado: un token de Sanctum.
- Que un link filtrado o capturado tenga un impacto mínimo (un solo uso, 15 minutos, hash en base de datos).
- Que las respuestas no permitan enumerar usuarios.

**Non-Goals:**
- Logout, restablecimiento de clave, registro, 2FA y autenticación SPA con cookies (ver `proposal.md`).
- Expiración de los tokens de Sanctum (`sanctum.expiration` sigue en `null`); se decide en un cambio aparte.
- Limpieza programada de `login_links` vencidos (se resuelve de forma oportunista, ver decisión 5).
- Historial o auditoría de links emitidos y usados.

## Decisions

### 1. Token de Sanctum en el cuerpo de la respuesta (modo token, no cookie)
Ambas vías emiten `$user->createToken('auth')->plainTextToken`, que se entrega en el JSON. El frontend es una aplicación separada (`FRONTEND_URL`) y el link se abre en el navegador, por lo que un token de API es más simple que la sesión por cookie.
- *Alternativa:* sesión SPA con cookies de Sanctum. Se descarta: exige dominios stateful y CSRF, y no hay un requisito de esa forma.

### 2. Tabla `login_links` propia en lugar de reutilizar `password_reset_tokens` o URLs firmadas
Columnas: `id` (uuid, PK), `user_id` (`foreignUuid`, **único**, con borrado en cascada), `token_hash` (string de 64, único), `expires_at` (con índice) y `timestamps`. No hay `used_at`: la tabla es temporal y no guarda historial. Modelo `LoginLink` con `HasUuids`.
- El índice único en `user_id` garantiza a nivel de base de datos un único link por usuario, y por tanto una cota al tamaño de la tabla.
- No se audita el uso de los links: se prioriza que la tabla no crezca. Si se necesitara auditoría, iría en un log aparte.
- *Alternativa:* `URL::temporarySignedRoute`. Se descarta: no es de un solo uso sin estado adicional, y la firma queda visible en la URL del frontend.
- *Alternativa:* `password_reset_tokens`. Se descarta: su clave primaria es el correo (no UUID) y mezcla dos propósitos distintos.

### 3. Token aleatorio de 64 caracteres con hash SHA-256
Se genera con `Str::random(64)`, se envía en claro por correo y se guarda `hash('sha256', $token)`. Al canjear se busca por el hash. No se usa `Hash::make` (bcrypt) porque el token tiene alta entropía y se necesita una búsqueda directa por índice, no una comparación contra un valor conocido.

### 4. Canje atómico y de un solo uso
El canje ocurre dentro de una transacción: se busca el `LoginLink` por hash con `lockForUpdate()`, se verifica `expires_at > now()`, se **elimina el registro** y se emite el token. El bloqueo hace que dos canjes simultáneos del mismo token no puedan tener éxito: el segundo no encuentra el registro. Cualquier causa de fallo (inexistente, ya canjeado, vencido) devuelve el mismo `ValidationException` (422) con un mensaje único; un link ya canjeado es indistinguible de uno inexistente, porque ya no existe.

### 5. Limpieza sin tareas programadas: a lo más un link por usuario
La tabla se mantiene acotada por tres mecanismos, sin depender del scheduler:
- Al canjear, el registro se elimina (decisión 4).
- Al emitir un link nuevo se hace `updateOrCreate` por `user_id` (o se elimina el previo y se crea el nuevo) dentro de una transacción, apoyado en el índice único de `user_id`.
- En esa misma solicitud se eliminan los links con `expires_at < now()` de cualquier usuario (consulta por índice).

Los únicos registros que persisten son links pendientes de usuarios que solicitaron acceso y no lo han usado, y se limpian en la siguiente solicitud de cualquier usuario una vez vencidos.
- *Alternativa:* `MassPrunable` con `model:prune` programado. Se descarta por ahora: añade una dependencia operativa (el scheduler) para un problema que la limpieza oportunista resuelve.

### 6. Respuesta uniforme y sin ventaja de tiempo observable en la solicitud de link
`POST /api/auth/link` responde 202 con el mismo mensaje tanto si el usuario existe como si no. El envío del correo se encola (`Mail::queue` con `ShouldQueue`) para que el tiempo de respuesta no delate la existencia del usuario. `QUEUE_CONNECTION` ya tiene tabla `jobs`; en pruebas se usa `Mail::fake()`.
- *Alternativa:* envío síncrono. Se descarta: el tiempo de respuesta distinguiría usuarios existentes de inexistentes.

### 7. Canje por POST y el link apunta al frontend
El correo contiene `{FRONTEND_URL}/auth/link?token=...`. La página del frontend lee el token y llama a `POST /api/auth/link/verify`. Un GET a la API nunca consume el token, así los escáneres de correo no lo gastan. La ruta del frontend se lee de una clave nueva `config('app.frontend_url')` (`env('FRONTEND_URL')`) para no usar `env()` fuera de config.

### 8. Rate limiting con `RateLimiter::for` en `AppServiceProvider`
Tres limitadores nombrados, aplicados con `throttle:<nombre>`:
- `login`: 5 por minuto por `email|ip`.
- `auth-link`: 3 por minuto por `email|ip` en la solicitud de link.
- `auth-link-verify`: 10 por minuto por IP.

Los valores son conservadores y se pueden ajustar sin tocar los specs, que solo exigen que exista un límite y que se responda 429.

### 9. Estructura según la arquitectura del proyecto
- `app/Http/Requests/Auth/` — `LoginRequest`, `RequestLinkRequest`, `VerifyLinkRequest` (validación de formato).
- `app/Data/Auth/` — `LoginData`, `RequestLinkData`, `VerifyLinkData` (spatie/laravel-data, construidos desde el `FormRequest`).
- `app/Actions/Auth/` — `LoginAction`, `RequestLinkAction`, `VerifyLinkAction` (lorisleiva/laravel-actions, con `AsAction`).
- `app/Http/Controllers/Auth/` — `LoginController` y `LinkController` (delgados: llaman a la Action y devuelven el Resource).
- `app/Http/Resources/Auth/AuthTokenResource.php` — `{ data: { token, user } }` (estructura nativa de `JsonResource`, con envoltorio `data`), reutilizado por ambas vías; el usuario se serializa sin campos ocultos.
- `app/Mail/LoginLinkMail.php` y `resources/views/emails/auth/link.blade.php` — el correo en texto simple/markdown, en español.
- `app/Models/LoginLink.php` y su migración.
- Las rutas se añaden a `routes/api.php` fuera del grupo `auth:sanctum`.

### 10. Las acciones comparten la emisión del token
La emisión de token y la actualización de `last_logged_in_at` se encapsulan en una Action reutilizable (`IssueAuthTokenAction`), llamada por `LoginAction` y por `VerifyLinkAction`, para no duplicar la lógica.

## Risks / Trade-offs

- [Links vencidos que nunca se canjean permanecen hasta la siguiente solicitud de cualquier usuario] → Es aceptable: la tabla está acotada a un registro por usuario (índice único) y el contenido es solo un hash ya vencido. Si el tráfico fuera muy bajo y esto importara, se añade `model:prune` programado.
- [Sin historial de links usados no hay auditoría de accesos] → Decisión consciente; `last_logged_in_at` registra el último acceso.
- [Un correo comprometido da acceso total] → Es inherente a la autenticación por link. Se mitiga con vigencia de 15 minutos, un solo uso y rate limiting.
- [Mensajes de error idénticos dificultan el diagnóstico del usuario legítimo] → Es intencional (anti-enumeración). El frontend debe indicar "solicita un nuevo link" ante cualquier 422 en el canje.
- [Si el worker de colas no corre, el correo no sale] → Documentar en las tareas que se requiere `queue:work` fuera de pruebas; `MAIL_MAILER=log` cubre desarrollo.
- [Marcar `email_verified_at` al canjear podría no ser deseado si luego se exige verificación por separado] → Se acepta ahora porque el link prueba control del correo; está registrado en el spec y es fácil de revertir.
- [Contención por `lockForUpdate` en el canje] → Despreciable: la consulta es por un índice único y la transacción es mínima.

## Migration Plan

1. Ejecutar la nueva migración (`php artisan migrate`); solo crea `login_links`, no altera tablas existentes.
2. Definir `FRONTEND_URL` en `.env` de cada entorno (y en `.env.testing`) y asegurar un mailer real y un worker de colas en producción.
3. Rollback: `php artisan migrate:rollback` elimina `login_links`; las rutas nuevas son aditivas, así que retirarlas no afecta nada existente.
