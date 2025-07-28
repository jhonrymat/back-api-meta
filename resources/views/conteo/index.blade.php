@extends('adminlte::page')

@section('title', 'Datos de envios')

@section('content')
    <div class="container">
        <h2 class="mb-4">📅 Estadísticas del día {{ \Carbon\Carbon::parse($fecha)->format('d M Y') }}</h2>

        <form method="GET" class="mb-4 d-flex gap-3 align-items-end">
            <div>
                <label for="fecha">Seleccionar fecha</label>
                <input type="date" id="fecha" name="fecha" class="form-control" value="{{ $fecha }}">
            </div>
            <button class="btn btn-primary">Consultar</button>
        </form>

        @forelse($datos as $phoneId => $registros)
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <strong>📞 Número (phone_id):</strong> {{ $phoneId }}
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Estado</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalDelDia = 0; @endphp
                            @foreach ($registros as $r)
                                <tr>
                                    <td>{{ ucfirst($r->status) }}</td>
                                    <td>{{ $r->total }}</td>
                                </tr>
                                @php $totalDelDia += $r->total; @endphp
                            @endforeach
                            <tr class="table-info">
                                <td><strong>Total del día</strong></td>
                                <td><strong>{{ $totalDelDia }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="alert alert-warning">
                No hay datos para esta fecha.
            </div>
        @endforelse
    </div>
@endsection
@section('css')
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.0.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@stop
