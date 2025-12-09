@extends('adminlte::page')

@section('title', 'Estadisticas')

@section('plugins.Chartjs', true)

@section('content_header')
    <h1>Estadisticas</h1>
@stop

@section('content')
    <form id="statisticsForm">
        @csrf
        <div class="row">
            <div class="col-lg-5">
                <label for="fechaInicio" class="form-label">Fecha y hora inicial</label>
                <input type="datetime-local" class="form-control" id="fechaInicio" name="fechaInicio" required>
            </div>
            <div class="col-lg-5">
                <label for="fechaFin" class="form-label">Fecha y hora final</label>
                <input type="datetime-local" class="form-control" id="fechaFin" name="fechaFin" required>
            </div>
            <div class="col-lg-5">
                <label for="selectPlantilla">Seleccione un número disponible</label>
                <select id="selectPlantilla" name="selectPlantilla" class="form-select form-control mb-3" required>
                    <option value="">Selecciona un Número</option>
                    @foreach ($numeros as $numero)
                        <option value="{{ $numero->id_telefono }}">{{ $numero->nombre }} - {{ $numero->numero }} -
                            {{ $numero->aplicacion->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <br>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </div>
        </div>
    </form>
    <br>
    <section class="content">
        <div class="container-fluid">
            <div class="row">

                <section class="col-lg-6 connectedSortable ui-sortable">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Estados de cargue: [<span id="startDate"></span> - <span
                                    id="endDate"></span>] </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                        </div>

                        <div class="card-body">
                            <table id="resporteTableStatus" class="display nowrap">
                                <thead>
                                    <tr>
                                        <th>Estado</th>
                                        <th>Cantidad</th>
                                        <th>Progreso</th>
                                        <th style="width: 40px"> % </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Enviados</td>
                                        <td id="sentCount">0</td>
                                        <td>
                                            <div class="progress progress-xs progress-striped active">
                                                <div id="sentProgressBar" class="progress-bar bg-primary"></div>
                                            </div>
                                        </td>
                                        <td><span id="sentPercentage" class="badge bg-primary"></span></td>
                                    </tr>
                                    <tr>
                                        <td>Entregado</td>
                                        <td id="deliveredCount">0</td>
                                        <td>
                                            <div class="progress progress-xs">
                                                <div id="deliveredProgressBar" class="progress-bar bg-secondary"></div>
                                            </div>
                                        </td>
                                        <td><span id="deliveredPercentage" class="badge bg-secondary"></span></td>
                                    </tr>
                                    <tr>
                                        <td>Fallidos</td>
                                        <td id="failedCount">0</td>
                                        <td>
                                            <div class="progress progress-xs">
                                                <div id="failedProgressBar" class="progress-bar bg-danger"></div>
                                            </div>
                                        </td>
                                        <td><span id="failedPercentage" class="badge bg-danger"></span></td>
                                    </tr>
                                    <tr>
                                        <td>Total</td>
                                        <td id="totalMessages">0</td>
                                        <td>
                                            <div class="progress progress-xs progress-striped active">
                                                <div class="progress-bar bg-success" style="width: 100%"></div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-success">100%</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>




                </section>


                <section class="col-lg-6 connectedSortable ui-sortable">
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">Confirmación mensaje: [<span id="startDate2"></span> - <span
                                    id="endDate2"></span>]</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <h4>Total de eventos: <span id="totalEventos"></span></h4>
                            <div class="chartjs-size-monitor">
                                <div class="chartjs-size-monitor-expand">
                                    <div class=""></div>
                                </div>
                                <div class="chartjs-size-monitor-shrink">
                                    <div class=""></div>
                                </div>
                            </div>
                            <canvas id="donutChart"
                                style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%; display: block; width: 389px;"
                                width="486" height="312" class="chartjs-render-monitor"></canvas>
                        </div>

                    </div>
                </section>

            </div>
            {{-- <div class="row">

                <section class="col-lg-12 connectedSortable ui-sortable">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Exportación de reporte</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="resporteTable" class="display nowrap" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Id</th>
                                            <th>Fecha Inicio</th>
                                            <th>Fecha Fin</th>
                                            <th>generar</th>
                                            <th>descargar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($reportes as $reporte)
                                            <tr> <!-- Agregamos esta línea para definir una fila de la tabla -->
                                                <td>{{ $reporte->id }}</td>
                                                <td>{{ $reporte->fechaInicio }}</td>
                                                <td>{{ $reporte->fechaFin }}</td>
                                                <td>
                                                    <a href="#"
                                                        onclick="exportarMensaje({{ $reporte->id }}); return false;"
                                                        class="btn btn-success btn-sm mb-2">
                                                        Crear
                                                    </a>
                                                </td>
                                                @if ($reporte->archivo && $reporte->archivoExiste())
                                                    <td>
                                                        <a href="{{ route('download', $reporte->id) }}"
                                                            class="btn btn-primary btn-sm mb-2">
                                                            <i class="fa fa-download"></i> Descargar
                                                        </a>
                                                    </td>
                                                @else
                                                    <td>
                                                        <span>
                                                            <p>Clic en crear..</p>
                                                        </span>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>




                </section>



            </div> --}}
        </div>
    </section>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.dataTables.css">
@stop

@section('js')
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.2/js/responsive.dataTables.js"></script>
    <script>
        var baseUrl = "{{ url('/') }}";
        var exportUrl = "{{ route('exportar-mensajes', '_id_') }}";
        var downloadUrl = "{{ route('download', '_id_') }}";
        $(document).ready(function() {
            $('#statisticsForm').submit(function(event) {
                event.preventDefault(); // Evitar el envío del formulario por defecto

                var formData = $(this).serialize(); // Obtener los datos del formulario

                // Mostrar SweetAlert de cargando
                Swal.fire({
                    title: 'Cargando...',
                    text: 'Por favor, espera mientras obtenemos los datos.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar una solicitud AJAX al controlador para obtener las estadísticas
                $.ajax({
                    url: '{{ route('get-statistics') }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        // Cerrar el SweetAlert de cargando
                        Swal.close();

                        // Destruir la DataTable antes de actualizar los datos
                        dataTable.destroy();

                        // Actualizar el contenido de la tabla con los nuevos datos de los reportes
                        var tableBody = $('#resporteTable tbody');
                        tableBody.empty(); // Limpiar el contenido actual de la tabla
                        // Iterar sobre los nuevos reportes y agregar filas a la tabla
                        response.reportes.forEach(function(reporte) {
                            var newRow = '<tr>' +
                                '<td>' + reporte.id + '</td>' +
                                '<td>' + reporte.fechaInicio + '</td>' +
                                '<td>' + reporte.fechaFin + '</td>' +
                                '<td><a href="#" onclick="exportarMensaje(' + reporte
                                .id +
                                ')" class="btn btn-success btn-sm mb-2">crear</a></td>' +
                                (reporte.archivo ? '<td><a href="' + downloadUrl
                                    .replace('_id_', reporte.id) +
                                    '" class="btn btn-primary btn-sm mb-2"><i class="fa fa-download"></i></a></td>' :
                                    '<td><span><p>esperando...</p></span></td>') +
                                '</tr>';
                            tableBody.append(newRow);
                        });

                        // Reinicializar la DataTable con los nuevos datos
                        dataTable = $('#resporteTable').DataTable({
                            responsive: true,
                            autoWidth: false,
                            "order": [
                                [0, "desc"]
                            ]
                        });

                        // Asignar los valores recibidos a las variables de la vista
                        $('#sentCount').text(response.sentCount);
                        $('#deliveredCount').text(response.deliveredCount);
                        $('#failedCount').text(response.failedCount);
                        $('#totalMessages').text(response.totalMessages);
                        $('#totalEventos').text(response.totalMessages);

                        $('#startDate').text(response.startDate);
                        $('#endDate').text(response.endDate);
                        $('#startDate2').text(response.startDate);
                        $('#endDate2').text(response.endDate);

                        $('#sentPercentage').text(response.sentPercentage + "%");
                        $('#deliveredPercentage').text(response.deliveredPercentage + "%");
                        $('#failedPercentage').text(response.failedPercentage + "%");

                        $('#sentProgressBar').css('width', response.sentPercentage + "%");
                        $('#deliveredProgressBar').css('width', response.deliveredPercentage +
                            "%");
                        $('#failedProgressBar').css('width', response.failedPercentage + "%");

                        var donutChartCanvas = $('#donutChart').get(0).getContext('2d');
                        var donutData = {
                            labels: [
                                'Enviados',
                                'Entregados',
                                'Fallidos',
                            ],
                            datasets: [{
                                data: [response.sentCount, response.deliveredCount,
                                    response.failedCount
                                ],
                                backgroundColor: ['#00c0ef', '#f39c12', '#f50854'],
                            }]
                        };
                        var donutOptions = {
                            maintainAspectRatio: false,
                            responsive: true,
                        };
                        // Crear gráfico de dona
                        new Chart(donutChartCanvas, {
                            type: 'doughnut',
                            data: donutData,
                            options: donutOptions
                        });
                    },
                    error: function(xhr, status, error) {
                        // Cerrar el SweetAlert de cargando
                        Swal.close();

                        // Mostrar el error con SweetAlert2
                        Swal.fire({
                            icon: 'error',
                            title: '¡Error!',
                            text: 'Hubo un problema con la solicitud: ' + error,
                            footer: '<a href>¿Necesitas ayuda?</a>'
                        });
                        console.error(error);
                    }
                });
            });
        });
    </script>
    <script>
        var dataTable = $('#resporteTable').DataTable({
            responsive: true,
            autoWidth: false,
            "order": [
                [0, "desc"]
            ]
        });

        new DataTable('#resporteTableStatus', {
            responsive: true,
        });
    </script>
    {{-- <script>
        function exportarMensaje(reporteId) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¿Deseas iniciar la exportación de los mensajes? Este proceso puede tardar unos momentos.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '¡Sí, exportar ahora!',
                cancelButtonText: 'Cancelar',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return $.ajax({
                        url: 'exportar-mensajes/' + reporteId,
                        type: 'GET',
                        beforeSend: function() {
                            Swal.showLoading();
                        }
                    }).fail(function(xhr, status, error) {
                        var errorMessage = 'No se pudo iniciar la exportación.';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage += ' ' + xhr.responseJSON.error;
                        }
                        Swal.fire('Error', errorMessage, 'error');
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire(
                        '✅ Exportación programada',
                        'Estamos generando tu archivo. Recibirás un enlace por WhatsApp o correo cuando esté listo.',
                        'success'
                    );
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire(
                        'Cancelado',
                        'No se inició la exportación.',
                        'info'
                    );
                }
            });
        }
    </script> --}}
@stop
