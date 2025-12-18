<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperación de contraseña</title>
    <style>
        body {
            background-color: #f4f6f8;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 480px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        }

        .header {
            background-color: #0f172a;
            padding: 20px;
            text-align: center;
            color: #ffffff;
        }

        .header h1 {
            font-size: 20px;
            margin: 0;
            font-weight: 600;
        }

        .content {
            padding: 25px 30px;
            font-size: 15px;
            line-height: 1.6;
        }

        .button {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff !important;
            padding: 12px 20px;
            margin: 20px 0;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
        }

        .footer {
            padding: 15px 20px;
            background: #f1f5f9;
            font-size: 12px;
            text-align: center;
            color: #6b7280;
        }

        .url {
            word-break: break-all;
            color: #2563eb;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="header">
        <h1>Recuperación de contraseña</h1>
    </div>

    <div class="content">
        <p>Hola,</p>

        <p>
            Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.
            Si fuiste tú, haz clic en el siguiente botón:
        </p>

        <p style="text-align: center;">
            <a href="{{ $url }}" class="button">Restablecer contraseña</a>
        </p>

        <p>
            Si el botón no funciona, copia y pega este enlace en tu navegador:
        </p>

        <p class="url">{{ $url }}</p>

        <p>
            Si no solicitaste un cambio de contraseña, simplemente ignora este mensaje.
        </p>
    </div>

    <div class="footer">
        © {{ date('Y') }} LARAVEL PRUEBAS — Todos los derechos reservados 2025.
    </div>

</div>

</body>
</html>
