<!-- resources/views/groups/index.blade.php -->
@extends('adminlte::page')

@section('title', 'Envios por plantillas')

@section('content_header')
    <h1>Grupo de Correo electronico</h1>
@stop

@section('content')
    @if (session('errors'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4>Errores en la Importación</h4>
            <ul>
                @foreach (session('errors') as $error)
                    <li>
                        Fila {{ $error['row'] }}:
                        @foreach ($error['errors'] as $message)
                            {{ $message }}
                        @endforeach
                    </li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('errorFile'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <p>Algunos registros tienen errores.
                <a href="{{ session('errorFile') }}" class="btn btn-primary">Descargar reporte de errores</a>
            </p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Integrantes del Grupo {{ $group->name }}</h2>
            </div>
            <div class="card-body">
                <h3>Crear Integrante</h3>
                <form action="{{ route('groups.addRecipient', $group->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-check">
                        <input type="radio" class="form-check-input" id="individual" name="method" value="individual"
                            checked>
                        <label class="form-check-label" for="individual">Individual</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" id="file" name="method" value="file">
                        <label class="form-check-label" for="file">Cargar Archivo</label>
                    </div>

                    {{-- Campos para ingreso individual --}}
                    <div id="individual-fields" class="mt-3">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="form-group">
                            <label for="name">Nombre</label>
                            <input type="text" class="form-control" id="name" name="name">
                        </div>
                    </div>

                    {{-- Campo para cargue masivo --}}
                    <div id="file-fields" class="mt-3" style="display: none;">
                        <div class="form-group">
                            <label for="file">Archivo (CSV)</label>
                            <input type="file" class="form-control" id="file" name="file" accept=".csv">
                            <small class="form-text text-muted">Sube un archivo CSV con las columnas "email" y
                                "name".</small>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">Enviar</button>
                </form>
            </div>
        </div>


        <div class="container">
            <h2>Grupo {{ $group->name }}</h2>

            {{-- Formulario de búsqueda --}}
            <form action="{{ route('groups.showEmails', $group->id) }}" method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Buscar por correo o nombre"
                        value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </form>

            {{-- Tabla con paginación --}}
            <h3>Cantidad de Integrantes: {{ $recipients->total() }}</h3>
            <div id="recipients-container">
                <table class="table mt-3">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Nombre</th>
                            <th>Fecha Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recipients as $email)
                            <tr>
                                <td>{{ $email->email }}</td>
                                <td>{{ $email->name }}</td>
                                <td>{{ $email->created_at }}</td>
                                <td>
                                    <form action="{{ route('groups.removeRecipient', [$group->id, $email->id]) }}"
                                        method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                    <a href="{{ route('groups.editRecipient', [$group->id, $email->id]) }}"
                                        class="btn btn-warning">Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- <div class="d-flex justify-content-center">
                    {{ $recipients->withQueryString()->links() }}
                </div> --}}
                <div class="d-flex justify-content-center">
                    {{ $recipients->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            </div>

        </div>

    </div>

@endsection
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); // Previene el comportamiento por defecto del botón

                const recipientId = this.getAttribute('data-id');
                const url = this.getAttribute('data-url');

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "No podrás revertir esta acción.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminarlo',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Crea un formulario dinámico para enviar la solicitud
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;

                        // Agrega los tokens de CSRF y DELETE
                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = '{{ csrf_token() }}';
                        form.appendChild(csrfToken);

                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'DELETE';
                        form.appendChild(methodInput);

                        // Agrega el formulario al documento y envíalo
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
    @if (session('success'))
        <script>
            Swal.fire({
                title: '¡Éxito!',
                text: "{{ session('success') }}",
                icon: 'success',
                confirmButtonText: 'Aceptar'
            });
        </script>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('recipients-container');

            container.addEventListener('click', function(event) {
                if (event.target.tagName === 'A' && event.target.closest('.pagination')) {
                    event.preventDefault();

                    const url = event.target.href;

                    fetch(url)
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const newContent = doc.querySelector('#recipients-container');
                            container.innerHTML = newContent.innerHTML;
                        })
                        .catch(error => console.error('Error:', error));
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const individualRadio = document.getElementById('individual');
            const fileRadio = document.getElementById('file');
            const individualFields = document.getElementById('individual-fields');
            const fileFields = document.getElementById('file-fields');

            individualRadio.addEventListener('change', function() {
                if (this.checked) {
                    individualFields.style.display = 'block';
                    fileFields.style.display = 'none';
                }
            });

            fileRadio.addEventListener('change', function() {
                if (this.checked) {
                    individualFields.style.display = 'none';
                    fileFields.style.display = 'block';
                }
            });
        });
    </script>

    <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>

    <script>
        Pusher.logToConsole = true;

        var pusher = new Pusher("{{ env('PUSHER_APP_KEY') }}", {
            cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
            encrypted: true
        });

        var channel = pusher.subscribe('import-channel');

        // 🔹 Escuchar evento con el namespace completo
        channel.bind('App\\Events\\ImportCompleted', function(data) {
            console.log("📢 Evento recibido:", data);

            Swal.fire({
                icon: 'success',
                title: 'Importación Completada',
                text: data.message,
                confirmButtonText: 'OK', // 🔹 Botón de confirmación
                allowOutsideClick: false, // 🔹 Evita cerrar fuera del cuadro
                allowEscapeKey: false // 🔹 Evita cerrar con tecla Escape
            });
        });

        // ❌ Importación Fallida
        channel.bind('App\\Events\\ImportFailed', function(data) {
            Swal.fire({
                icon: 'error',
                title: 'Error en la Importación',
                text: data.message,
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
        });
    </script>

@endsection
