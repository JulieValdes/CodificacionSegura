<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Correo</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
    <table width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600px" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="background-color: #4caf50; padding: 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">¡Verifica tu correo electrónico!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px; text-align: center; color: #333;">
                            <p style="font-size: 16px; line-height: 1.5; margin: 0;">Gracias por registrarte. Por favor, utiliza el siguiente código para verificar tu correo electrónico:</p>
                            <h2 style="margin: 20px 0; font-size: 32px; color: #4caf50;">{{ $verificationCode }}</h2>
                            <p style="font-size: 14px; color: #555; margin: 0;">Este código expirará en <strong>10 minutos</strong>.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f4f4f4; padding: 20px; text-align: center; color: #777; font-size: 12px;">
                            <p style="margin: 0;">Si no solicitaste este correo, puedes ignorarlo de manera segura.</p>
                            <p style="margin: 10px 0 0;">&copy; {{ date('Y') }} </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
