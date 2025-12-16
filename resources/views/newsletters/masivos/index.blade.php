@extends('adminlte::page')
@section('title', 'Boletines')
@section('content_header')
    <h1>Boletines</h1>
@stop
@section('content')
    <div class="card mt-4">
        <div class="card-header">
            <h3>Envíos Recientes</h3>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Newsletter</th>
                        <th>Destinatarios</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Progreso</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enviosRecientes as $envio)
                        <tr>
                            <td>{{ $envio->id }}</td>
                            <td>{{ $envio->newsletter->subject }}</td>
                            <td>{{ $envio->numero_destinatarios }}</td>
                            <td>
                                @if ($envio->status === 'Pendiente')
                                    <span class="badge badge-warning">{{ $envio->status }}</span>
                                @elseif($envio->status === 'Completado')
                                    <span class="badge badge-success">{{ $envio->status }}</span>
                                @else
                                    <span class="badge badge-danger">{{ $envio->status }}</span>
                                @endif
                            </td>
                            <td>{{ $envio->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                @if ($envio->batch_id && $envio->status === 'Pendiente')
                                    <div class="progress" style="height: 20px;" id="progress-{{ $envio->id }}">
                                        <div class="progress-bar" style="width: 0%">0%</div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay envíos recientes</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


@endsection

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.0.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    @if ($enviosRecientes->where('status', 'Pendiente')->count() > 0)
        <script>
            // Polling para envíos pendientes
            @foreach ($enviosRecientes->where('status', 'Pendiente') as $envio)
                (function pollEnvio_{{ $envio->id }}() {
                    $.ajax({
                        url: '/admin/email-envios/{{ $envio->id }}/status',
                        success: function(data) {
                            if (data.batch) {
                                const prog = Math.round(data.batch.progress);
                                const bar = $('#progress-{{ $envio->id }} .progress-bar');
                                bar.css('width', prog + '%').text(prog + '%');

                                if (data.batch.finished) {
                                    bar.removeClass('progress-bar-animated');
                                    bar.addClass('bg-success');
                                    setTimeout(() => location.reload(), 2000);
                                } else {
                                    setTimeout(pollEnvio_{{ $envio->id }}, 5000);
                                }
                            }
                        }
                    });
                })();
            @endforeach
        </script>
    @endif
@stop
