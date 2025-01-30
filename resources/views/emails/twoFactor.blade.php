<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificacion de Inicio de Sesion</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">
    <table width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600px" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td style="background-color: #007bff; padding: 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Verificación en dos pasos</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px; text-align: center; color: #333;">
                            <p style="font-size: 16px; line-height: 1.5; margin: 0;">Hemos detectado un intento de inicio de sesión en tu cuenta.</p>
                            <p style="font-size: 16px; margin: 20px 0;">Si tú has iniciado esta acción, utiliza el siguiente código de verificación:</p>
                            <h2 style="margin: 20px 0; font-size: 32px; color: #007bff;">{{ $verificationCode }}</h2>
                            <p style="font-size: 14px; color: #555; margin: 0;">Este código expirará en <strong>10 minutos</strong>.</p>
                            <p style="font-size: 14px; color: #555; margin-top: 20px;">Si no has intentado iniciar sesión, por favor ignora este correo o ponte en contacto con soporte.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f4f4f4; padding: 20px; text-align: center; color: #777; font-size: 12px;">
                            <p style="margin: 0;">Para garantizar la seguridad de tu cuenta, nunca compartas este código con nadie.</p>
                            <p style="margin: 10px 0 0;">&copy; {{ date('Y') }} </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
