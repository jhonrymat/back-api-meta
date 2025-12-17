@extends('adminlte::page')

@section('title', 'Monitoreo de Envío #' . $envio->id)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Monitoreo de Envío #{{ $envio->id }}</h1>
        <a href="{{ route('newsletters.masivos') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Volver
        </a>
    </div>
@stop

@section('content')
    {{-- Información General --}}
    <div class="row">
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
                            <strong>Newsletter:</strong><br>
                            {{ $envio->newsletter->subject }}
                        </div>
                        <div class="col-md-3">
                            <strong>Destinatarios:</strong><br>
                            {{ $envio->numero_destinatarios }}
                        </div>
                        <div class="col-md-3">
                            <strong>Creado:</strong><br>
                            {{ $envio->created_at->format('Y-m-d H:i:s') }}
                        </div>
                        <div class="col-md-3">
                            <strong>Batch ID:</strong><br>
                            <code style="font-size: 10px;">{{ $envio->batch_id ?: 'N/A' }}</code>
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
                                        @if ($envio->status === 'Pendiente')
                                            <span class="badge badge-warning">{{ $envio->status }}</span>
                                        @elseif($envio->status === 'Completado')
                                            <span class="badge badge-success">{{ $envio->status }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ $envio->status }}</span>
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
                                    <span class="info-box-text">Enviados</span>
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

                    {{-- Barra de Progreso --}}
                    <div class="mt-4">
                        <h5>Progreso General</h5>
                        <div class="progress" style="height: 40px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar"
                                id="main-progress-bar" style="width: 0%; font-size: 18px; line-height: 40px;">
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

        {{-- Acciones --}}
        @if ($envio->batch_id)
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h3 class="card-title">
                            <i class="fa fa-cog"></i> Acciones
                        </h3>
                    </div>
                    <div class="card-body">
                        @if ($envio->status === 'Pendiente')
                            <button onclick="cancelarEnvio()" class="btn btn-danger">
                                <i class="fa fa-stop"></i> Cancelar Envío
                            </button>
                        @endif

                        @if (in_array($envio->status, ['Fallido', 'Completado con errores']))
                            <form method="POST" action="{{ route('email-envios.retry', $envio->id) }}"
                                style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-warning"
                                    onclick="return confirm('¿Reintentar emails fallidos?')">
                                    <i class="fa fa-redo"></i> Reintentar Fallidos
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
@stop

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
    </style>
@stop

@section('js')
<script src="https://code.jquery.com/jquery-3.7.0.js" crossorigin="anonymous"></script>
    <script>
        const envioId = {{ $envio->id }};
        let updateInterval = null;

        function actualizarEstado() {
            $.ajax({
                url: `/admin/email-envios/${envioId}/status`,
                method: 'GET',
                success: function(response) {
                    if (!response.success) return;

                    $('#last-update').text(new Date().toLocaleTimeString());

                    const batch = response.batch;
                    if (batch) {
                        $('#total-count').text(batch.total);
                        $('#processed-count').text(batch.processed);
                        $('#pending-count').text(batch.pending);
                        $('#failed-count').text(batch.failed);
                        $('#progress-percent').text(Math.round(batch.progress) + '%');

                        const progressBar = $('#main-progress-bar');
                        const progreso = Math.round(batch.progress);

                        progressBar.css('width', progreso + '%');
                        progressBar.text(progreso + '%');

                        if (batch.finished) {
                            clearInterval(updateInterval);

                            progressBar.removeClass('progress-bar-animated bg-info');
                            progressBar.addClass(batch.failed > 0 ? 'bg-warning' : 'bg-success');

                            $('#status-message')
                                .removeClass('alert-info')
                                .addClass(batch.failed > 0 ? 'alert-warning' : 'alert-success')
                                .html('<i class="fa fa-check-circle"></i> <strong>Envío completado</strong>');

                            $('#status-badge').html(
                                batch.failed > 0 ?
                                '<span class="badge badge-warning">Completado con errores</span>' :
                                '<span class="badge badge-success">Completado</span>'
                            );
                        } else {
                            $('#status-message')
                                .removeClass('alert-success alert-danger alert-warning')
                                .addClass('alert-info')
                                .html(`
                                    <i class="fa fa-spinner fa-spin"></i>
                                    <strong>Procesando...</strong>
                                    ${batch.pending} emails pendientes
                                `);
                        }
                    }
                },
                error: function(xhr) {
                    $('#status-message')
                        .removeClass('alert-info')
                        .addClass('alert-danger')
                        .html('<i class="fa fa-exclamation-triangle"></i> Error al obtener estado');

                    if (xhr.status === 404) {
                        clearInterval(updateInterval);
                    }
                }
            });
        }

        // Primera actualización
        actualizarEstado();

        // Actualizar cada 3 segundos
        updateInterval = setInterval(actualizarEstado, 3000);

        // Limpiar al salir
        window.addEventListener('beforeunload', function() {
            if (updateInterval) clearInterval(updateInterval);
        });

        // Función cancelar
        function cancelarEnvio() {
            if (!confirm('¿Estás seguro de cancelar este envío?')) return;

            $.ajax({
                url: `/admin/email-envios/${envioId}/cancel`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Envío cancelado');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error al cancelar');
                }
            });
        }
    </script>
@stop
