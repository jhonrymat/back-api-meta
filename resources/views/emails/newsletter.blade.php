<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $newsletter->subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 850px;
            margin: 20px auto;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        .logo-header {
            text-align: center;
            padding: 20px;
        }
        .email-header {
            background-color: #12b5ec;
            color: white;
            text-align: center;
            padding: 10px;
        }
        .email-body {
            padding: 20px;
        }
        .email-footer {
            background-color: #f1f1f1;
            text-align: center;
            padding: 10px;
            font-size: 0.9em;
            color: #555;
        }
        .unsubscribe {
            color: #d9534f;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="logo-header">
            <img src="https://contratacionlocal.com/storage/general/logo.png" alt="Logo" style="max-width: 150px; margin-bottom: 10px;">
        </div>
        <div class="email-header">
            <h1>Contratación Local</h1>
        </div>
        <!-- Body -->
        <div class="email-body">
            <h2>{{ $newsletter->subject }}</h2>
            {!! $content !!}
        </div>
        <!-- Footer -->
        <div class="email-footer">
            <p>
                En caso de que usted no desee recibir más información de este tipo, por favor haga clic en
                <a href="#" class="unsubscribe">Cancelar suscripción</a>.
            </p>
            <p>
                Powered by <strong>Maddigo</strong><br>
                Usted ha recibido este correo electrónico porque se encuentra registrado en nuestro sistema Mailing. <br>
                <em>Todos los derechos reservados.</em>
            </p>
        </div>
    </div>
</body>
</html>
