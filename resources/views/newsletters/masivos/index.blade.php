@extends('adminlte::page')
@section('title', 'Boletines')
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Envíos de Email</h1>
        <a href="{{ route('newsletters.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Volver a Newsletters
        </a>
    </div>
@stop
@section('content')
    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ $message }}
        </div>
    @endif

    @if ($message = Session::get('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ $message }}
        </div>
    @endif

    {{-- Filtros --}}
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('newsletters.masivos') }}" class="form-inline">
                <label class="mr-2">Filtrar por estado:</label>
                <select name="status" class="form-control mr-2">
                    <option value="all">Todos</option>
                    <option value="Pendiente" {{ request('status') === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="Completado" {{ request('status') === 'Completado' ? 'selected' : '' }}>Completado
                    </option>
                    <option value="Fallido" {{ request('status') === 'Fallido' ? 'selected' : '' }}>Fallido</option>
                    <option value="Cancelado" {{ request('status') === 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
                <button type="submit" class="btn btn-primary mr-2">
                    <i class="fa fa-filter"></i> Filtrar
                </button>
                <a href="{{ route('newsletters.masivos') }}" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Limpiar
                </a>
            </form>
        </div>
    </div>

    {{-- Tabla de envíos --}}
    <div class="card">
        <div class="card-body">
            <table id="enviosTable" class="table table-striped table-bordered shadow-lg">
                <thead class="bg-primary text-white">
                    <tr>
                        <th>ID</th>
                        <th>Newsletter</th>
                        <th>Destinatarios</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Progreso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($envios as $envio)
                        <tr id="envio-row-{{ $envio->id }}">
                            <td>{{ $envio->id }}</td>
                            <td>{{ Str::limit($envio->newsletter->subject, 40) }}</td>
                            <td>{{ $envio->numero_destinatarios }}</td>
                            <td id="status-cell-{{ $envio->id }}">
                                @if ($envio->status === 'Pendiente')
                                    <span class="badge badge-warning">{{ $envio->status }}</span>
                                @elseif($envio->status === 'Completado')
                                    <span class="badge badge-success">{{ $envio->status }}</span>
                                @elseif($envio->status === 'Fallido' || $envio->status === 'Completado con errores')
                                    <span class="badge badge-danger">{{ $envio->status }}</span>
                                @elseif($envio->status === 'Cancelado')
                                    <span class="badge badge-secondary">{{ $envio->status }}</span>
                                @endif

                                {{-- Barra de progreso --}}
                                @if ($envio->batch_id && $envio->status === 'Pendiente')
                                    <div id="progress-{{ $envio->id }}" class="mt-2">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                                role="progressbar" style="width: 0%">
                                                0%
                                            </div>
                                        </div>
                                        <small class="text-muted" id="progress-text-{{ $envio->id }}">
                                            Iniciando...
                                        </small>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $envio->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <span id="stats-{{ $envio->id }}">-</span>
                            </td>
                            <td>
                                {{-- Monitorear --}}
                                @if ($envio->batch_id)
                                    <a href="{{ route('email-envios.monitor', $envio->id) }}"
                                        class="btn btn-info btn-sm mb-1" title="Monitorear en detalle" target="_blank">
                                        <i class="fa fa-chart-line"></i>
                                    </a>
                                @endif

                                {{-- Reintentar fallidos --}}
                                @if (in_array($envio->status, ['Fallido', 'Completado con errores']))
                                    <form method="POST" action="{{ route('email-envios.retry', $envio->id) }}"
                                        style="display: inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-warning btn-sm mb-1"
                                            title="Reintentar fallidos"
                                            onclick="return confirm('¿Reintentar los emails fallidos?')">
                                            <i class="fa fa-redo"></i>
                                        </button>
                                    </form>
                                @endif

                                {{-- Cancelar --}}
                                @if ($envio->status === 'Pendiente' && $envio->batch_id)
                                    <button onclick="cancelarEnvio({{ $envio->id }})" class="btn btn-danger btn-sm mb-1"
                                        title="Cancelar envío">
                                        <i class="fa fa-stop"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">No hay envíos registrados</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $envios->links() }}
        </div>
    </div>
@endsection

@section('css')
    <style>
        .progress {
            background-color: #f3f4f6;
            border-radius: 4px;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }
    </style>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.0.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        // Polling para envíos pendientes
        const enviosPendientes = [];

        @foreach ($envios as $envio)
            @if ($envio->batch_id && $envio->status === 'Pendiente')
                enviosPendientes.push({
                    id: {{ $envio->id }},
                    batchId: "{{ $envio->batch_id }}",
                    interval: null
                });
            @endif
        @endforeach

        function actualizarEstadoEnvio(envio) {
            $.ajax({
                url: `/admin/email-envios/${envio.id}/status`,
                method: 'GET',
                success: function(response) {
                    if (!response.success || !response.batch) return;

                    const batch = response.batch;
                    const progreso = Math.round(batch.progress);
                    const barra = $(`#progress-${envio.id} .progress-bar`);
                    const texto = $(`#progress-text-${envio.id}`);
                    const stats = $(`#stats-${envio.id}`);

                    // Actualizar barra
                    if (barra.length) {
                        barra.css('width', progreso + '%');
                        barra.text(progreso + '%');
                    }

                    // Actualizar texto
                    if (texto.length) {
                        let msg = `${batch.processed} de ${batch.total} enviados`;
                        if (batch.failed > 0) {
                            msg += ` · ${batch.failed} fallidos`;
                        }
                        texto.html(msg);
                    }

                    // Actualizar stats
                    stats.html(`${batch.processed}/${batch.total}`);

                    // Si finalizó
                    if (batch.finished) {
                        clearInterval(envio.interval);

                        if (barra.length) {
                            barra.removeClass('progress-bar-animated bg-info');
                            barra.addClass(batch.failed > 0 ? 'bg-warning' : 'bg-success');
                        }

                        // Recargar página después de 2 segundos
                        setTimeout(() => location.reload(), 2000);
                    }
                },
                error: function(xhr) {
                    console.error('Error actualizando envío:', xhr);
                    if (xhr.status === 404) {
                        clearInterval(envio.interval);
                    }
                }
            });
        }

        // Iniciar polling
        enviosPendientes.forEach(function(envio) {
            actualizarEstadoEnvio(envio);
            envio.interval = setInterval(() => actualizarEstadoEnvio(envio), 5000);
        });

        // Limpiar al salir
        window.addEventListener('beforeunload', function() {
            enviosPendientes.forEach(e => clearInterval(e.interval));
        });

        // Función para cancelar envío
        function cancelarEnvio(id) {
            if (!confirm('¿Estás seguro de cancelar este envío?')) {
                return;
            }

            $.ajax({
                url: `/admin/email-envios/${id}/cancel`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Envío cancelado correctamente');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Error al cancelar el envío');
                }
            });
        }
    </script>
@stop
