@extends('adminlte::page')

@section('title', 'Envios por plantillas')

@section('content_header')
    <h1>Envios por plantillas</h1>
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
                <th>Nombre plantilla</th>
                <th>N° de envios</th>
                <th>Mensaje</th>
                <th>Etiquetas</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody style="text-align: center">
            @foreach ($envios as $app)
                <tr id="envio-row-{{ $app->id }}">
                    <th>{{ $app->id }}</th>
                    <th>{{ $app->nombrePlantilla }}</th>
                    <td>{{ $app->numeroDestinatarios }}</td>
                    <td title="{{ $app->body }}">{{ Str::limit($app->body, 50) }}</td>
                    <td>
                        @if ($app->tag && count($app->tag) > 0)
                            @foreach ($app->tag as $tId)
                                @if (isset($tags[$tId]))
                                    <span
                                        style="background-color: {{ $tags[$tId]->color }}; padding: 3px; border-radius: 4px; font-size: 12px; margin-right: 5px;">
                                        {{ $tags[$tId]->nombre }}
                                    </span>
                                @endif
                            @endforeach
                        @else
                            <span style="padding: 3px; border-radius: 4px; font-size: 12px; margin-right: 5px;">Sin
                                etiqueta</span>
                        @endif
                    </td>
                    <td>{{ $app->created_at->format('Y-m-d H:i') }}</td>
                    <td id="status-cell-{{ $app->id }}">
                        @if ($app->status == 'Pendiente')
                            <span class="badge badge-warning">{{ $app->status }}</span>
                        @elseif($app->status == 'Completado')
                            <span class="badge badge-success">{{ $app->status }}</span>
                        @elseif($app->status == 'Fallido')
                            <span class="badge badge-danger">{{ $app->status }}</span>
                        @else
                            <span class="badge badge-secondary">Sin estado</span>
                        @endif

                        {{-- Barra de progreso para envíos pendientes con batch_id --}}
                        @if ($app->batch_id && $app->status === 'Pendiente')
                            <div id="progress-{{ $app->id }}" class="mt-2" style="width: 100%;">
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                        role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0"
                                        aria-valuemax="100">
                                        0%
                                    </div>
                                </div>
                                <small class="text-muted" id="progress-text-{{ $app->id }}">
                                    Iniciando...
                                </small>
                            </div>
                        @endif
                    </td>
                    <td>
                        {{-- Ver detalles --}}
                        <a data-toggle="modal" data-target="#modal-show-{{ $app->id }}"
                            class="btn btn-warning btn-sm mb-2" title="Ver detalles">
                            <i class="fa fa-eye"></i>
                        </a>

                        {{-- Monitorear en página dedicada (si está pendiente) --}}
                        @if ($app->batch_id && $app->status === 'Pendiente')
                            <a href="{{ route('envios.monitor', $app->id) }}" class="btn btn-info btn-sm mb-2"
                                title="Monitorear en detalle" target="_blank">
                                <i class="fa fa-chart-line"></i>
                            </a>
                        @endif
                    </td>
                </tr>
                {{-- Modal show --}}
                @include('envios.modals.show-modal')
            @endforeach
        </tbody>
    </table>
@endsection

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
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
        // Inicializar DataTable
        new DataTable('#enviosTable', {
            "order": [
                [0, "desc"]
            ], // Ordenar por ID descendente
            "pageLength": 25
        });

        // =====================================================
        // SISTEMA DE POLLING PARA ENVÍOS PENDIENTES
        // =====================================================
        const enviosPendientes = [];

        @foreach ($envios as $app)
            @if ($app->batch_id && $app->status == 'Pendiente')
                enviosPendientes.push({
                    id: {{ $app->id }},
                    batchId: "{{ $app->batch_id }}",
                    interval: null
                });
            @endif
        @endforeach

        // Función para actualizar estado de un envío
        function actualizarEstadoEnvio(envio) {
            $.ajax({
                url: '{{ url('admin/envios') }}/' + envio.id + '/status',
                method: 'GET',
                success: function(response) {
                    if (!response.success) {
                        console.warn('Error en respuesta para envío ' + envio.id);
                        return;
                    }

                    const batchInfo = response.batch;

                    if (!batchInfo) {
                        console.warn('No hay info de batch para envío ' + envio.id);
                        return;
                    }

                    // Actualizar barra de progreso
                    const progreso = Math.round(batchInfo.progress);
                    const barra = $('#progress-' + envio.id + ' .progress-bar');
                    const texto = $('#progress-text-' + envio.id);

                    if (barra.length) {
                        barra.css('width', progreso + '%');
                        barra.attr('aria-valuenow', progreso);
                        barra.text(progreso + '%');
                    }

                    // Actualizar texto de progreso
                    if (texto.length) {
                        texto.text(`${batchInfo.processed} de ${batchInfo.total} enviados`);

                        if (batchInfo.failed > 0) {
                            texto.append(` · <span class="text-danger">${batchInfo.failed} fallidos</span>`);
                        }
                    }

                    // Verificar si completó o falló
                    if (batchInfo.finished) {
                        // Detener polling
                        clearInterval(envio.interval);

                        // Actualizar barra de progreso
                        if (barra.length) {
                            barra.removeClass('progress-bar-animated progress-bar-striped bg-info');

                            if (batchInfo.failed === 0 || batchInfo.failed < batchInfo.total * 0.1) {
                                // Éxito (menos del 10% fallidos)
                                barra.addClass('bg-success');
                                if (texto.length) {
                                    texto.html('<span class="text-success">✓ Completado</span>');
                                }
                            } else {
                                // Algunos fallos
                                barra.addClass('bg-warning');
                                if (texto.length) {
                                    texto.html('<span class="text-warning">⚠ Completado con errores</span>');
                                }
                            }
                        }

                        // Actualizar badge de estado
                        const statusCell = $('#status-cell-' + envio.id);
                        const badge = statusCell.find('.badge');

                        if (response.envio.status === 'Completado') {
                            badge.removeClass('badge-warning').addClass('badge-success');
                            badge.text('Completado');
                        } else if (response.envio.status === 'Fallido') {
                            badge.removeClass('badge-warning').addClass('badge-danger');
                            badge.text('Fallido');
                        }

                        // Notificación opcional
                        console.log(`✅ Envío ${envio.id} finalizado`);

                        // Opcional: Mostrar notificación toast
                        // toastr.success(`Envío #${envio.id} completado`);
                    } else if (batchInfo.cancelled) {
                        // Envío cancelado
                        clearInterval(envio.interval);

                        if (barra.length) {
                            barra.removeClass('progress-bar-animated bg-info');
                            barra.addClass('bg-secondary');
                        }

                        if (texto.length) {
                            texto.html('<span class="text-muted">⊗ Cancelado</span>');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error actualizando envío ' + envio.id + ':', error);

                    // Si el error es 404, el batch ya no existe
                    if (xhr.status === 404) {
                        clearInterval(envio.interval);
                    }
                }
            });
        }

        // Iniciar polling para cada envío pendiente
        enviosPendientes.forEach(function(envio) {
            // Primera actualización inmediata
            actualizarEstadoEnvio(envio);

            // Polling cada 5 segundos
            envio.interval = setInterval(function() {
                actualizarEstadoEnvio(envio);
            }, 5000);
        });

        // Limpiar intervalos al salir de la página
        window.addEventListener('beforeunload', function() {
            enviosPendientes.forEach(function(envio) {
                if (envio.interval) {
                    clearInterval(envio.interval);
                }
            });
        });

        // =====================================================
        // OPCIONAL: Notificaciones Desktop
        // =====================================================

        // Pedir permiso para notificaciones
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission();
        }

        function mostrarNotificacion(titulo, mensaje) {
            if ("Notification" in window && Notification.permission === "granted") {
                new Notification(titulo, {
                    body: mensaje,
                    icon: '/favicon.ico'
                });
            }
        }
    </script>
@stop
