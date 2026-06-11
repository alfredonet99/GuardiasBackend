<!doctype html>
<html>
<head>
  <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; line-height:1.5; color:#111827;">
  <h2 style="margin:0 0 12px 0; font-size:18px;">
    Recordatorio del sistema: Guardia pendiente
  </h2>

  <p style="margin:0 0 10px 0;">
    Hola equipo.
  </p>

  <p style="margin:0 0 10px 0;">
    El sistema detectó que actualmente no existe una guardia activa registrada.
  </p>

  <p style="margin:0 0 10px 0;">
    Para continuar con la operación, ingresa al sistema desde este enlace:
  </p>

  <p style="margin:0 0 14px 0;">
    <a href="{{ $url }}" style="color:#1d4ed8; text-decoration:underline;">
      {{ $url }}
    </a>
  </p>

  <hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0;">

  <p style="margin:0; color:#6b7280; font-size:12px;">
    Mensaje automático. Si ya se inició una guardia recientemente, puedes ignorar este aviso.
  </p>
</body>
</html>