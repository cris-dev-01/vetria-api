## 1. Configuración y base de datos

- [x] 1.1 Agregar `'frontend_url' => env('FRONTEND_URL')` a `config/app.php` y definir `FRONTEND_URL` en `.env.testing`; verificar con `php artisan tinker --env=testing` que `config('app.frontend_url')` devuelve un valor
- [x] 1.2 Crear la migración de `login_links` (`uuid('id')->primary()`, `foreignUuid('user_id')` único con borrado en cascada, `token_hash` string(64) único, `expires_at` con índice, `timestamps`; sin `used_at`) con `declare(strict_types=1)`; verificar que `php artisan migrate` (con `.env.testing`) y `migrate:rollback` se ejecutan sin errores
- [x] 1.3 Crear el modelo `App\Models\LoginLink` con `HasUuids`, casts de fechas y relación `user()`, y agregar `loginLinks()` (`hasMany`) a `User`; verificar con un test unitario que se crea un registro con id UUID y su relación

## 2. Rate limiting y rutas

- [x] 2.1 Definir en `AppServiceProvider` los limitadores `login` (5/min por email+IP), `auth-link` (3/min por email+IP) y `auth-link-verify` (10/min por IP); verificar con tests que el intento que excede el límite responde 429
- [x] 2.2 Registrar en `routes/api.php`, fuera de `auth:sanctum`, `POST /login`, `POST /auth/link` y `POST /auth/link/verify` con su `throttle` correspondiente; verificar con `php artisan route:list --path=api` que aparecen las tres rutas y que un GET a `/api/auth/link/verify` responde 405

## 3. Capa de entrada (FormRequests y DTOs)

- [x] 3.1 Crear `LoginRequest`, `RequestLinkRequest` y `VerifyLinkRequest` en `app/Http/Requests/Auth/` con las reglas de los specs (`email` requerido y con formato de correo, `password` requerido, `token` requerido); verificar con tests que los campos faltantes o inválidos responden 422 con errores por campo
- [x] 3.2 Crear `LoginData`, `RequestLinkData` y `VerifyLinkData` en `app/Data/Auth/` (spatie/laravel-data) construidos desde el FormRequest; verificar con un test unitario que cada DTO se hidrata con los datos validados

## 4. Actions

- [x] 4.1 Crear `IssueAuthTokenAction` (`AsAction`) que emita el token de Sanctum con `createToken` y actualice `last_logged_in_at`; verificar con un test que el token devuelto autentica una ruta con `auth:sanctum`
- [x] 4.2 Crear `LoginAction` que valide correo y clave y delegue en `IssueAuthTokenAction`, lanzando `ValidationException` con el mismo mensaje para clave incorrecta y correo inexistente; verificar con tests de los escenarios de credenciales válidas, clave incorrecta y correo inexistente (sin cambio de `last_logged_in_at` cuando falla)
- [x] 4.3 Crear `RequestLinkAction` que elimine los links vencidos de cualquier usuario y, si el usuario existe, reemplace su link previo (a lo más uno por usuario), genere un token con `Str::random(64)`, guarde su hash SHA-256 con vencimiento de 15 minutos y encole el correo; si no existe, no cree link ni envíe correo; verificar con tests que se guarda solo el hash, que el link anterior es reemplazado (un solo registro por usuario), que los vencidos de otros usuarios se eliminan y que para un correo inexistente no se envía correo
- [x] 4.4 Crear `VerifyLinkAction` que, dentro de una transacción con `lockForUpdate()`, valide que el link exista y no esté vencido, elimine el registro, complete `email_verified_at` si estaba vacío y delegue en `IssueAuthTokenAction`; verificar con tests de token válido (el registro deja de existir), ya canjeado, vencido e inexistente (mismo mensaje de error y sin cambios en el usuario cuando falla)

## 5. Correo

- [x] 5.1 Crear `App\Mail\LoginLinkMail` (`ShouldQueue`) y la vista `resources/views/emails/auth/link.blade.php` en español con la URL `config('app.frontend_url')/auth/link?token=...` y la indicación de los 15 minutos de vigencia; verificar con `Mail::fake()` que se encola al destinatario correcto y que la URL contiene el dominio del frontend y el token

## 6. Controllers y Resource

- [x] 6.1 Crear `AuthTokenResource` (`{ data: { token, user } }`, sin campos ocultos del usuario); verificar con un test que la respuesta no incluye `password` ni `remember_token`
- [x] 6.2 Crear `LoginController` y `LinkController` (`request` y `verify`) en `app/Http/Controllers/Auth/`, delgados: reciben el DTO, llaman a la Action y devuelven el Resource (200) o el mensaje genérico (202); verificar con los tests de feature de la sección 7

## 7. Pruebas de feature

- [x] 7.1 Escribir `tests/Feature/Auth/PasswordLoginTest.php` (PHPUnit, `RefreshDatabase`, `.env.testing`) que cubra todos los escenarios de `specs/password-authentication/spec.md`; verificar con `vendor/bin/phpunit tests/Feature/Auth/PasswordLoginTest.php`
- [x] 7.2 Escribir `tests/Feature/Auth/LinkAuthenticationTest.php` que cubra todos los escenarios de `specs/link-authentication/spec.md`, incluidos respuesta uniforme 202, vigencia, un solo uso, eliminación del registro al canjear, reemplazo del link anterior, limpieza de vencidos, GET con 405 y rate limiting; verificar con `vendor/bin/phpunit tests/Feature/Auth/LinkAuthenticationTest.php`

## 8. Cierre

- [x] 8.1 Ejecutar `vendor/bin/pint` y `vendor/bin/phpunit` completo con `.env.testing`; verificar que el estilo no reporta cambios pendientes y que toda la suite pasa
- [x] 8.2 Revisar que todas las clases nuevas incluyan `declare(strict_types=1)` y que ningún nombre de clase, ruta, tabla ni texto use la palabra "magic-link"; verificar con una búsqueda (`grep -ri "magic" app routes database resources tests`) sin resultados
