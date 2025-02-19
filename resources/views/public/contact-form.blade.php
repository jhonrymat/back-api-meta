<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Contacto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="container mt-4">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>¡Éxito!</strong> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>¡Error!</strong> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <h3 class="text-center">Agregar Contacto</h3>

    <form id="createFormContactos" method="POST" action="{{ url('/public/store-contact') }}">
        @csrf
        <input type="hidden" name="user_id" value="{{ $userId }}">
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="row">
            <div class="col-md-6">
                <label for="nombre">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required>

                <label for="correo">Correo</label>
                <input type="email" class="form-control" id="correo" name="correo">
            </div>

            <div class="col-md-6">
                <label for="telefono">Teléfono</label>
                <input type="tel" class="form-control" id="telefono" name="telefono" required>

                <label for="notas">Notas</label>
                <textarea class="form-control" id="notas" name="notas"></textarea>
            </div>

            <div class="col-md-12">
                <label for="etiqueta">Etiquetas</label>
                <select id="etiqueta" name="etiqueta[]" class="form-select" multiple required>
                    @foreach ($tags as $tag)
                        <option value="{{ $tag->id }}">{{ $tag->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3 w-100">Guardar</button>
    </form>

    <script>
        $(document).ready(function() {
            $("#createFormContactos").submit(function(event) {
                event.preventDefault();

                let formData = $(this).serialize();

                $.ajax({
                    url: "{{ url('/public/store-contact') }}",
                    type: "POST",
                    data: formData,
                    success: function(response) {
                        Swal.fire({
                            title: "Éxito",
                            text: response.message,
                            icon: "success",
                            confirmButtonText: "OK"
                        });

                        // Limpiar formulario
                        $("#createFormContactos")[0].reset();
                    },
                    error: function(xhr) {
                        let errorMessage = "Hubo un error al guardar el contacto.";
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }

                        Swal.fire({
                            title: "Error",
                            text: errorMessage,
                            icon: "error",
                            confirmButtonText: "Intentar de nuevo"
                        });
                    }
                });
            });
        });
    </script>

</body>

</html>
