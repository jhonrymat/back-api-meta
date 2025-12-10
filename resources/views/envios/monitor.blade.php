@extends('adminlte::page')

@section('title', 'Monitoreo de Envío #' . $envio->id)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Monitoreo de Envío #{{ $envio->id }}</h1>
        <a href="{{ url()->previous() }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Volver
        </a>
    </div>
@stop
@section('content')
    <div class="row">
        {{-- Información General --}}
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">
                        <i class="fa fa-info-circle"></i> Información del Envío
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Plantilla:</strong><br>
                            {{ $envio->nombrePlantilla }}
                        </div>
                        <div class="col-md-3">
                            <strong>Destinatarios:</strong><br>
                            {{ $envio->numeroDestinatarios }}
                        </div>
                        <div class="col-md-3">
                            <strong>Creado:</strong><br>
                            {{ $envio->created_at->format('Y-m-d H:i:s') }}
                        </div>
                        <div class="col-md-3">
                            <strong>Batch ID:</strong><br>
                            <code>{{ $envio->batch_id ?: 'N/A' }}</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Estado Actual --}}
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title">
                        <i class="fa fa-chart-bar"></i> Estado Actual
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fa fa-inbox"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Estado</span>
                                    <span class="info-box-number" id="status-badge">
                                        @if ($envio->status == 'Pendiente')
                                            <span class="badge badge-warning">{{ $envio->status }}</span>
                                        @elseif($envio->status == 'Completado')
                                            <span class="badge badge-success">{{ $envio->status }}</span>
                                        @elseif($envio->status == 'Fallido')
                                            <span class="badge badge-danger">{{ $envio->status }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $envio->status }}</span>
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-success">
                                    <i class="fa fa-check"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Procesados</span>
                                    <span class="info-box-number" id="processed-count">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning">
                                    <i class="fa fa-clock"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Pendientes</span>
                                    <span class="info-box-number" id="pending-count">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger">
                                    <i class="fa fa-times"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Fallidos</span>
                                    <span class="info-box-number text-danger" id="failed-count">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary">
                                    <i class="fa fa-list"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total</span>
                                    <span class="info-box-number" id="total-count">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-box">
                                <span class="info-box-icon bg-gradient-info">
                                    <i class="fa fa-percentage"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Progreso</span>
                                    <span class="info-box-number" id="progress-percent">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Barra de Progreso Grande --}}
                    <div class="mt-4">
                        <h5>Progreso General</h5>
                        <div class="progress" style="height: 40px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                                 role="progressbar"
                                 id="main-progress-bar"
                                 style="width: 0%; font-size: 18px; line-height: 40px;">
                                0%
                            </div>
                        </div>
                    </div>

                    {{-- Mensaje de Estado --}}
                    <div id="status-message" class="alert alert-info mt-4">
                        <i class="fa fa-spinner fa-spin"></i> Cargando estado del envío...
                    </div>

                    {{-- Última Actualización --}}
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            Última actualización: <span id="last-update">-</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Logs Recientes (Opcional) --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h3 class="card-title">
                        <i class="fa fa-history"></i> Actividad Reciente
                    </h3>
                </div>
                <div class="card-body">
                    <div id="activity-log" style="max-height: 300px; overflow-y: auto;">
                        <p class="text-muted">Esperando actualizaciones...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .info-box {
            min-height: 90px;
            margin-bottom: 15px;
        }
        .progress {
            background-color: #f3f4f6;
            border-radius: 8px;
        }
        .progress-bar {
            transition: width 0.6s ease;
            font-weight: bold;
        }
        #activity-log p {
            margin-bottom: 8px;
            padding: 8px;
            border-left: 3px solid #007bff;
            background: #f8f9fa;
        }
    </style>
@stop

@section('js')
    <script>
        const envioId = {{ $envio->id }};
        const batchId = "{{ $envio->batch_id }}";
        let updateInterval = null;
        let activityLog = [];

        // Función para agregar actividad al log
        function agregarActividad(mensaje, tipo = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const icon = tipo === 'success' ? '✓' : tipo === 'error' ? '✗' : 'ℹ';
            const color = tipo === 'success' ? '#28a745' : tipo === 'error' ? '#dc3545' : '#17a2b8';

            const logEntry = `
                <p style="border-left-color: ${color};">
                    <strong>${icon} ${timestamp}</strong> - ${mensaje}
                </p>
            `;

            activityLog.unshift(logEntry);

            // Mantener solo las últimas 20 entradas
            if (activityLog.length > 20) {
                activityLog.pop();
            }

            $('#activity-log').html(activityLog.join(''));
        }

        // Función principal para actualizar estado
        function actualizarEstado() {
            $.ajax({
                url: `/admin/envios/${envioId}/status`,
                method: 'GET',
                success: function(response) {
                    if (!response.success) {
                        console.error('Error en respuesta del servidor');
                        return;
                    }

                    const envio = response.envio;
                    const batch = response.batch;

                    // Actualizar última actualización
                    $('#last-update').text(new Date().toLocaleTimeString());

                    if (batch) {
                        // Actualizar contadores
                        $('#total-count').text(batch.total);
                        $('#processed-count').text(batch.processed);
                        $('#pending-count').text(batch.pending);
                        $('#failed-count').text(batch.failed);
                        $('#progress-percent').text(Math.round(batch.progress) + '%');

                        // Actualizar barra de progreso
                        const progressBar = $('#main-progress-bar');
                        const progreso = Math.round(batch.progress);

                        progressBar.css('width', progreso + '%');
                        progressBar.attr('aria-valuenow', progreso);
                        progressBar.text(progreso + '%');

                        // Actualizar mensaje de estado
                        if (batch.finished) {
                            clearInterval(updateInterval);

                            progressBar.removeClass('progress-bar-animated progress-bar-striped bg-info');
                            progressBar.addClass('bg-success');

                            $('#status-message')
                                .removeClass('alert-info')
                                .addClass('alert-success')
                                .html('<i class="fa fa-check-circle"></i> <strong>Envío completado</strong>');

                            // Actualizar badge de estado
                            $('#status-badge').html('<span class="badge badge-success">Completado</span>');

                            agregarActividad('Envío completado exitosamente', 'success');

                            // Opcional: Reproducir sonido o mostrar notificación
                            if ("Notification" in window && Notification.permission === "granted") {
                                new Notification('Envío Completado', {
                                    body: `El envío #${envioId} ha finalizado`,
                                    icon: '/favicon.ico'
                                });
                            }

                        } else if (batch.cancelled) {
                            clearInterval(updateInterval);

                            progressBar.removeClass('progress-bar-animated bg-info');
                            progressBar.addClass('bg-secondary');

                            $('#status-message')
                                .removeClass('alert-info')
                                .addClass('alert-warning')
                                .html('<i class="fa fa-ban"></i> <strong>Envío cancelado</strong>');

                            agregarActividad('Envío cancelado', 'error');

                        } else {
                            // En progreso
                            $('#status-message')
                                .removeClass('alert-success alert-danger alert-warning')
                                .addClass('alert-info')
                                .html(`
                                    <i class="fa fa-spinner fa-spin"></i>
                                    <strong>Procesando...</strong>
                                    ${batch.pending} mensajes pendientes
                                `);
                        }

                        // Log de progreso (solo cuando hay cambios significativos)
                        const currentProgress = Math.floor(batch.progress / 10) * 10; // Redondear a múltiplos de 10
                        const lastProgress = parseInt($('#progress-percent').data('last-logged') || 0);

                        if (currentProgress > lastProgress && currentProgress % 10 === 0) {
                            agregarActividad(`Progreso: ${currentProgress}% completado`);
                            $('#progress-percent').data('last-logged', currentProgress);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error actualizando estado:', error);

                    $('#status-message')
                        .removeClass('alert-info')
                        .addClass('alert-danger')
                        .html('<i class="fa fa-exclamation-triangle"></i> Error al obtener estado del envío');

                    agregarActividad('Error al actualizar estado', 'error');

                    // Si es 404, detener polling
                    if (xhr.status === 404) {
                        clearInterval(updateInterval);
                    }
                }
            });
        }

        // Pedir permiso para notificaciones
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission();
        }

        // Primera actualización inmediata
        agregarActividad('Iniciando monitoreo del envío');
        actualizarEstado();

        // Actualizar cada 3 segundos
        updateInterval = setInterval(actualizarEstado, 3000);

        // Limpiar al salir
        window.addEventListener('beforeunload', function() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
    </script>
@stop
