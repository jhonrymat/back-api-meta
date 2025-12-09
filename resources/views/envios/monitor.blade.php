@extends('adminlte::page')

@section('title', 'Monitor de envios')

@section('content_header')
    <h1>monitor de envios</h1>
@stop

@section('content')

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>Monitoreo de Envío #{{ $envio->id }}</h3>
                <p class="text-muted mb-0">{{ $envio->nombrePlantilla }}</p>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h5>Estado</h5>
                            <span
                                class="badge badge-{{ $envio->status === 'Completado' ? 'success' : ($envio->status === 'Pendiente' ? 'warning' : 'danger') }} badge-lg">
                                {{ $envio->status }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h5>Destinatarios</h5>
                            <p class="h4">{{ $envio->numeroDestinatarios }}</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h5>Procesados</h5>
                            <p class="h4" id="processed">-</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h5>Fallidos</h5>
                            <p class="h4 text-danger" id="failed">-</p>
                        </div>
                    </div>
                </div>

                <div class="progress mb-3" style="height: 30px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" id="progressBar"
                        style="width: 0%">
                        0%
                    </div>
                </div>

                <div id="statusMessage" class="alert alert-info">
                    Cargando estado del envío...
                </div>

                @if ($envio->batch_id)
                    <div class="text-center">
                        <small class="text-muted">Batch ID: {{ $envio->batch_id }}</small>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        let envioId = {{ $envio->id }};
        let batchId = "{{ $envio->batch_id }}";

        // Actualizar cada 3 segundos
        let interval = setInterval(actualizarEstado, 3000);

        function actualizarEstado() {
            $.ajax({
                url: `/envios/${envioId}/status`,
                method: 'GET',
                success: function(response) {
                    if (response.batch) {
                        const batch = response.batch;

                        $('#processed').text(batch.processed);
                        $('#failed').text(batch.failed);
                        $('#progressBar').css('width', batch.progress + '%');
                        $('#progressBar').text(Math.round(batch.progress) + '%');

                        if (batch.finished) {
                            clearInterval(interval);
                            $('#statusMessage').removeClass('alert-info').addClass('alert-success');
                            $('#statusMessage').text('✅ Envío completado');
                            $('#progressBar').removeClass('progress-bar-animated');
                        } else if (batch.cancelled) {
                            clearInterval(interval);
                            $('#statusMessage').removeClass('alert-info').addClass('alert-warning');
                            $('#statusMessage').text('⚠️ Envío cancelado');
                        } else {
                            $('#statusMessage').text(`🔄 Procesando... (${batch.pending} pendientes)`);
                        }
                    }
                },
                error: function() {
                    $('#statusMessage').removeClass('alert-info').addClass('alert-danger');
                    $('#statusMessage').text('❌ Error al obtener estado');
                }
            });
        }

        // Primera actualización inmediata
        actualizarEstado();
    </script>

@endsection
