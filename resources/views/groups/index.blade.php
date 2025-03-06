@extends('adminlte::page')

@section('title', 'Grupos de Correos')

@section('content_header')
    <h1>Grupos de Correos</h1>
@stop

@section('content')
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <strong>¡Información Importante!</strong>
        Los contactos para hacer envíos de correos electrónicos se pueden seleccionar desde las
        <a href="{{ route('tags.index') }}" class="alert-link">etiquetas ya creadas</a>.
        Solo los contactos que tengan un correo registrado serán adjuntados para el envío.
        <br><br>
        Si necesitas agregar un nuevo contacto, puedes hacerlo desde la sección de
        <a href="{{ route('contactos.index') }}" class="alert-link">contactos</a>.
        <br><br>
        <strong>Solo se debera crear grupos.</strong> Si un contacto no tiene un número de celular único,
        por favor verifica su información antes de continuar.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Gestión de Grupos</h2>
                {{-- Formulario de búsqueda --}}
                <form action="{{ route('groups.index') }}" method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Buscar por nombre"
                        value="{{ request('search') }}">
                    <button type="submit" class="btn btn-light">Buscar</button>
                </form>
            </div>
            <div class="card-body">
                <p>Cantidad total de grupos: {{ $groups->total() }}</p>
                <a href="{{ route('groups.create') }}" class="btn btn-primary mb-3">Crear Nuevo Grupo</a>

                {{-- Tabla de Grupos --}}
                <table class="table table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>Cantidad de Integrantes</th>
                            <th>Fecha Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $group)
                            <tr>
                                <td>{{ $group->name }}</td>
                                <td>{{ $group->user_emails_count }}</td>
                                <td>{{ $group->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('groups.showEmails', $group->id) }}" class="btn btn-info btn-sm">
                                        Ver Emails
                                    </a>
                                    <a href="{{ route('groups.edit', $group->id) }}" class="btn btn-success btn-sm">
                                        Editar
                                    </a>
                                    <button class="btn btn-danger btn-sm delete-group-btn" data-id="{{ $group->id }}"
                                        data-url="{{ route('groups.destroy', $group->id) }}">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No se encontraron grupos</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- Paginación --}}
                <div class="d-flex justify-content-center">
                    {{ $groups->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    {{-- Agrega estilos personalizados si es necesario --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
@endsection

@section('js')
    {{-- SweetAlert para confirmación de eliminación --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-group-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('data-url');

                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "No podrás revertir esta acción.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = url;

                            const csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '_token';
                            csrfInput.value = '{{ csrf_token() }}';
                            form.appendChild(csrfInput);

                            const methodInput = document.createElement('input');
                            methodInput.type = 'hidden';
                            methodInput.name = '_method';
                            methodInput.value = 'DELETE';
                            form.appendChild(methodInput);

                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>

    {{-- Mostrar mensajes de éxito --}}
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
