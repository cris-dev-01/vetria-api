<x-mail::message>
# Hola, {{ $user->name }}

Usa el siguiente botón para ingresar a {{ config('app.name') }}. El link es de un solo uso y vence en {{ $expiresInMinutes }} minutos.

<x-mail::button :url="$url">
Ingresar
</x-mail::button>

Si no solicitaste este acceso, puedes ignorar este correo.

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
