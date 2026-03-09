<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Código</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background:#f5f5f5; padding:30px;">
    <div style="max-width:600px; margin:0 auto; background:#ffffff; border-radius:12px; padding:30px; box-shadow:0 4px 18px rgba(0,0,0,.08);">
        <h2 style="margin-top:0; color:#111827;">Hola, {{ $name }}</h2>

        <p style="font-size:15px; color:#374151;">
            Recibimos un intento de inicio de sesión en tu cuenta.
        </p>

        <p style="font-size:15px; color:#374151;">
            Tu código es:
        </p>

        <div style="font-size:32px; font-weight:bold; letter-spacing:8px; text-align:center; margin:25px 0; color:#111827;">
            {{ $code }}
        </div>

        <p style="font-size:14px; color:#6b7280;">
            Este código vence en 10 minutos.
        </p>

        <p style="font-size:14px; color:#6b7280;">
            Si tú no intentaste iniciar sesión, ignora este correo.
        </p>
    </div>
</body>
</html>