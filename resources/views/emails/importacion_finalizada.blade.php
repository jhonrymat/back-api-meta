<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Resultado de la Importación</title>
</head>

<body style="font-family: Arial, sans-serif; color: #333;">
    <h1 style="color: #2c3e50;">📥 Importación completada</h1>

    @if (count($omitidas) > 0)
        <p>Se importaron los contactos, pero algunas filas fueron omitidas por los siguientes motivos:</p>
        <ul>
            @foreach ($omitidas as $fila)
                <li>
                    <strong>Fila {{ $fila['fila'] }}</strong>: {{ $fila['motivo'] }}<br>
                    <em>Teléfono:</em> {{ $fila['telefono'] }}
                </li>
            @endforeach
        </ul>
        <p>Te recomendamos revisar el archivo e intentar importar nuevamente las filas omitidas si es necesario.</p>
    @else
        <p>✅ ¡Todos los contactos fueron importados exitosamente! 🎉</p>
    @endif

    <hr>
    <small>Este es un mensaje automático generado por el sistema de importación de contactos.</small>
</body>

</html>
