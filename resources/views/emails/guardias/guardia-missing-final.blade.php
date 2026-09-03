<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Guardia no registrada</title>
</head>

<body style="margin:0; padding:0; background:#f4f4f4; font-family:Arial, Helvetica, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center" style="padding:30px 15px;">
            <table width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:600px; background:#ffffff; border-radius:8px; overflow:hidden;">
                <tr>
                    <td align="center" style=" background:#1f2937; padding:25px; color:#ffffff;">
                         <h1 style="margin:0; font-size:24px; font-weight:600;">
                            Guardia no registrada
                        </h1>
                    </td>
                </tr>

                <tr>
                    <td style="padding:35px 30px;">
                        <p style="color:#374151; font-size:16px; line-height:1.6;">
                            Se confirma que no se registro la guardia durante el periodo establecido.
                        </p>

                        <table width="100%" cellpadding="0" cellspacing="0" style="margin:25px 0; background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px;">
                            <tr>
                                <td style="padding:20px;">
                                    <p style=" margin:0 0 10px 0; color:#111827; font-size:15px;">
                                        <strong>Inicio del periodo:</strong><br>
                                        {{ $inicio->format('d/m/Y H:i') }}
                                    </p>

                                    <p style="margin:0; color:#111827; font-size:15px;">
                                        <strong>Fin del periodo:</strong><br>
                                        {{ $fin->format('d/m/Y H:i') }}
                                    </p>

                                </td>
                            </tr>
                        </table>

                        <p style="color:#374151; font-size:16px; line-height:1.6;">
                            El periodo concluyó sin ningún registro de guardia.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:0px; background:#f9fafb; color:#6b7280; font-size:12px;">
                        Este mensaje fue generado automáticamente por el sistema.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>