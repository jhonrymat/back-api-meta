@extends('adminlte::page')
@section('title', 'Boletines')
@section('content_header')
    <h1>Boletines</h1>
@stop
@section('content')
    <div class="container">
        {{-- Formulario de búsqueda --}}
        <form action="{{ route('newsletters.index') }}" method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre"
                    value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </form>
        {{-- Tabla de boletines --}}
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Listado de Boletines</h2>
            </div>
            <div class="card-body">
                <a href="{{ route('newsletters.create') }}" class="btn btn-success mb-3">Crear Boletín</a>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Asunto</th>
                            <th>Con copia</th>
                            <th>Adjunto</th>
                            <th>Fecha de creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($newsletters as $newsletter)
                            <tr>
                                <td>{{ $newsletter->name }}</td>
                                <td>{{ $newsletter->subject }}</td>
                                <td>{{ $newsletter->copy_email ? 'Sí' : 'No' }}</td>
                                <td>
                                    @if ($newsletter->has_attachment)
                                        <a href="{{ $newsletter->attachment_url }}" target="_blank">Link</a>
                                    @else
                                        No
                                    @endif
                                </td>
                                <td>{{ $newsletter->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <button class="btn btn-success btn-sm send-test-btn" data-bs-toggle="modal"
                                        data-bs-target="#sendTestModal"
                                        data-url="{{ route('newsletters.sendTest', $newsletter->id) }}">
                                        Enviar Prueba
                                    </button>
                                    <button class="btn btn-primary btn-sm send-newsletter-btn" data-bs-toggle="modal"
                                        data-bs-target="#sendNewsletterModal"
                                        data-url="{{ route('newsletters.send', $newsletter->id) }}">
                                        Enviar Boletín
                                    </button>
                                    <a href="{{ route('newsletters.edit', $newsletter->id) }}"
                                        class="btn btn-warning btn-sm">Editar</a>
                                    <button class="btn btn-danger btn-sm delete-btn" data-id="{{ $newsletter->id }}"
                                        data-url="{{ route('newsletters.destroy', $newsletter->id) }}">Eliminar</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No hay boletines creados</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{-- Paginación --}}
                <div class="d-flex justify-content-center">
                    {{ $newsletters->withQueryString()->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
    <!-- Modal para Enviar Boletín de Prueba -->
    <div class="modal fade" id="sendTestModal" tabindex="-1" aria-labelledby="sendTestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="sendTestForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="sendTestModalLabel">Enviar Boletín de Prueba</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    {{-- nombre --}}
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="test_name">Nombre de Prueba</label>
                            <input type="name" name="test_name" id="test_name" class="form-control"
                                placeholder="Ingresa un nombre" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="test_email">Correo de Prueba</label>
                            <input type="email" name="test_email" id="test_email" class="form-control"
                                placeholder="Ingresa un correo" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary">Enviar Prueba</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal para Enviar Boletín -->
    <div class="modal fade" id="sendNewsletterModal" tabindex="-1" aria-labelledby="sendNewsletterModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="sendNewsletterForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="sendNewsletterModalLabel">Enviar Boletín</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Cómo deseas enviar este boletín?</p>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_type" id="immediate" value="immediate"
                                checked>
                            <label class="form-check-label" for="immediate">
                                Enviar Inmediatamente
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="send_type" id="scheduled"
                                value="scheduled">
                            <label class="form-check-label" for="scheduled">
                                Programar Envío
                            </label>
                        </div>
                        <div id="schedule-fields" style="display: none;" class="mt-3">
                            <label for="scheduled_date">Fecha y Hora</label>
                            <input type="datetime-local" name="scheduled_date" id="scheduled_date" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
@endsection
@section('js')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); // Evitar comportamiento por defecto
                const newsletterId = this.getAttribute('data-id');
                const url = this.getAttribute('data-url');
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta acción no se puede deshacer.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Crear un formulario dinámico para enviar la solicitud
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        // Agregar tokens de CSRF y DELETE
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
                        // Agregar el formulario al documento y enviarlo
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
        @if (session('success'))
            Swal.fire({
                title: '¡Éxito!',
                text: "{{ session('success') }}",
                icon: 'success',
                confirmButtonText: 'Aceptar'
            });
        @endif
    </script>
    <script>
        document.querySelectorAll('.send-test-btn').forEach(button => {
            button.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                const form = document.getElementById('sendTestForm');
                form.setAttribute('action', url); // Actualiza la acción del formulario
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
        document.querySelectorAll('.send-newsletter-btn').forEach(button => {
            button.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                document.getElementById('sendNewsletterForm').setAttribute('action', url);
            });
        });
        const immediateRadio = document.getElementById('immediate');
        const scheduledRadio = document.getElementById('scheduled');
        const scheduleFields = document.getElementById('schedule-fields');
        immediateRadio.addEventListener('change', function() {
            if (this.checked) {
                scheduleFields.style.display = 'none';
            }
        });
        scheduledRadio.addEventListener('change', function() {
            if (this.checked) {
                scheduleFields.style.display = 'block';
            }
        });
    </script>
@endsection
