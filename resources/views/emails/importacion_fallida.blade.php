<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error en la Importación</title>
</head>
<body>
    <h2>❌ La importación de contactos ha fallado.</h2>

    <p>Se encontraron los siguientes errores durante el proceso:</p>

    <ul>
        @foreach ($errores as $campo => $mensajes)
            @foreach ($mensajes as $mensaje)
                <li><strong>{{ $campo }}:</strong> {{ $mensaje }}</li>
            @endforeach
        @endforeach
    </ul>

    <p>Por favor revisa el archivo e intenta corregir los errores antes de volver a importar.</p>
</body>
</html>
