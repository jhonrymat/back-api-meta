@extends('adminlte::page')

@section('title', 'Contratación local')

@section('content_header')
    <h1>Solicitudes de Contratacion local</h1>
@stop

@section('content')

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <table id="enviosTable" class="table table-striped table-bordered shadow-lg mt-4 display compact" style="width:100%">
        <thead class="bg-primary text-white">
            <tr>
                <th>No</th>
                <th>Empresa</th>
                <th>Fecha de creación</th>
                <th>Fecha de inicio</th>
                <th>Contrato</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody style="text-align: center">
            @foreach ($solicitudes as $app)
                <tr>
                    <th>{{ $app->id }}</th>
                    <th>{{ $app->empresa }}</th>
                    <td>{{ $app->contrato }} {{ $app->created_at }}</td>
                    <td>{{ $app->fecha_inicio }}</td>
                    <td>
                        <a href="{{ $app->contrato == 'MO' ? env('PROJECT2_URL_MANO') : env('PROJECT2_URL_SERVICIO') }}/{{ $app->id_pdf }}"
                            target="_blank" rel="noopener noreferrer" style="text-decoration: none">
                            {{ $app->codigo_contrato }}
                        </a>
                        <span
                            class="badge {{ $app->estado == 'publicado' ? 'badge-primary' : 'badge-danger' }} badge-pill">{{ $app->estado }}</span>
                    </td>
                    <td>
                        <select class="form-select status-select" data-id="{{ $app->id }}">
                            <option value="pendiente" {{ $app->status == 'pendiente' ? 'selected' : '' }}>Pendiente
                            </option>
                            <option value="enviado" {{ $app->status == 'enviado' ? 'selected' : '' }}>Enviado</option>
                            <option value="cancelado" {{ $app->status == 'cancelado' ? 'selected' : '' }}>Cancelado
                            </option>
                        </select>
                        <span
                            class="badge {{ $app->status == 'enviado' ? 'badge-success' : ($app->status == 'cancelado' ? 'badge-danger' : 'badge-warning') }} badge-pill">{{ $app->status }}</span>
                    </td>
                    <td>
                        <a data-toggle="modal" data-target="#modal-show-{{ $app->id }}"
                            class="btn btn-warning btn-sm mb-2" title="Ver">
                            <i class="fa fa-eye"></i>
                        </a>
                        <a href="{{ route('enviar.solicitud', $app->id) }}" class="btn btn-primary btn-sm mb-2">
                            <i class="fa fa-paper-plane"></i>
                        </a>
                    </td>
                </tr>
                {{-- modal show --}}
                @include('cl.modals.show-modal')
            @endforeach
        </tbody>
    </table>
@endsection
@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.0.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            var table = new DataTable('#enviosTable', {
                "order": [
                    [0, "desc"]
                ],
                "processing": true,
                "serverSide": false, // Si estás usando AJAX para obtener los datos, ponlo en true
                "paging": true
            });

            // Delegar evento para elementos dinámicos en la tabla
            $(document).on('change', '.status-select', function() {
                var status = $(this).val();
                var id = $(this).data('id');
                var statusUrl = "{{ route('update.status.clocal') }}";
                var badge = $(this).closest('td').find('.badge');

                $.ajax({
                    url: statusUrl,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id,
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            let badgeClass = 'badge-warning';
                            if (status === 'enviado') badgeClass = 'badge-success';
                            if (status === 'cancelado') badgeClass = 'badge-danger';

                            badge.removeClass('badge-warning badge-success badge-danger')
                                .addClass(badgeClass)
                                .text(status.charAt(0).toUpperCase() + status.slice(1));

                            Swal.fire({
                                icon: 'success',
                                title: 'Estado actualizado',
                                text: response.message,
                                timer: 1500
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error al actualizar el estado.'
                        });
                    }
                });
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


@stop
