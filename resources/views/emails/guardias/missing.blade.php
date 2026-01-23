<!doctype html>
<html>
<head>
  <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif;">
  <h2>⚠️ No hay guardia activa</h2>

  <p>
    En este momento el sistema detectó que <strong>no existe una guardia activa</strong>.
  </p>

  <p>
    Por favor inicia una guardia desde el siguiente enlace:
  </p>

  <p>
    <a href="{{ $url }}" style="display:inline-block;padding:10px 14px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;">
      Iniciar guardia
    </a>
  </p>

  <p style="color:#666;font-size:12px;">
    Si no puedes abrir el botón, copia y pega este link:<br>
    {{ $url }}
  </p>

  <p>Saludos.</p>
</body>
</html>
