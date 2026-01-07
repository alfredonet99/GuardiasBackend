<!doctype html>
<html>
<head>
  <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif;">
  <h2>Guardia cerrada por el sistema</h2>

  <p>Hola {{ $guardia->user?->name ?? 'Usuario' }},</p>

  <p>
    Tu guardia fue cerrada automáticamente por el sistema después de 27 horas de ser abierta.
  </p>

  <ul>
    <li><strong>ID Guardia:</strong> {{ $guardia->id }}</li>
    <li><strong>Inicio:</strong> {{ optional($guardia->dateInit)->format('d/m/Y H:i') }}</li>
    <li><strong>Cierre:</strong> {{ optional($guardia->dateFinish)->format('d/m/Y H:i') }}</li>
    <li><strong>Estatus:</strong> Cerrado por sistema (3)</li>
  </ul>

  <p>Saludos.</p>
</body>
</html>
