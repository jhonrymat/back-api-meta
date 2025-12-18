@extends('adminlte::page')

@section('title', 'Corrección de Contratos')

@section('content')
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">
                    <i class="fas fa-tools"></i> Módulo de Corrección de Contratos
                </h2>

                <!-- Alertas -->
                <div id="alertContainer"></div>

                <!-- Sección 1: Buscar Contrato -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">1. Buscar Contrato (Orden)</h5>
                    </div>
                    <div class="card-body">
                        <form id="formBuscarContrato">
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="contrato_id" class="form-label">ID del Contrato:</label>
                                    <input type="number" class="form-control" id="contrato_id" name="contrato_id"
                                        placeholder="Ej: 3443" required>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Buscar Contrato
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Resultados del Contrato -->
                        <div id="resultadosContrato" class="mt-4" style="display: none;">
                            <hr>
                            <h6 class="text-success">Contrato Encontrado:</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <tbody id="datosContrato"></tbody>
                                </table>
                            </div>

                            <button type="button" class="btn btn-warning btn-sm" onclick="abrirModalEditarContrato()">
                                <i class="fas fa-edit"></i> Modificar Información del Contrato
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Necesidades -->
                <div class="card mb-4" id="seccionNecesidades" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">2. Necesidades del Contrato</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Contrato ID</th>
                                        <th>Orden ID</th>
                                        <th>Nombre</th>
                                        <th>Municipio</th>
                                        <th>Status</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaNecesidades"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sección 3: Resultados -->
                <div class="card mb-4" id="seccionResultados" style="display: none;">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">3. Resultados de la Necesidad</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Necesidad ID</th>
                                        <th>Empresa</th>
                                        <th>NIT</th>
                                        <th>Municipio</th>
                                        <th>Observaciones</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaResultados"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Contrato (Orden) -->
    <div class="modal fade" id="modalEditarContrato" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Modificar Información del Contrato
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form id="formEditarContrato">
                    <div class="modal-body">
                        {{-- mostrar informacion de ayuda para el usuario --}}
                        <div role="alert"
                            style="background:#cfe2ff; color:#084298; border:1px solid #b6d4fe; padding:12px 16px; border-radius:6px; font-family:Arial, sans-serif;">
                            Los estados permitidos son: <strong>publicado</strong> y <strong>cerrado</strong>
                        </div>
                        <br>
                        <input type="hidden" id="edit_orden_id">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Nota:</strong> Solo se modificará la información del contrato, no las relaciones.
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label"><strong>ID de la Orden:</strong></label>
                                <input type="text" class="form-control" id="edit_orden_id_display" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><strong>Campo a modificar:</strong></label>
                                <select class="form-control" id="edit_contrato_campo" required>
                                    <option value="">Seleccionar...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><strong>Nuevo Valor:</strong></label>
                                <input type="text" class="form-control" id="edit_contrato_valor">
                                <small class="text-muted">
                                    Deja vacío o escribe "null" para borrar este campo
                                </small>
                                <small class="form-text text-primary" id="edit_contrato_hint"></small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6><i class="fas fa-database"></i> Valor Actual:</h6>
                                        <p id="edit_contrato_valor_actual" class="mb-0 font-weight-bold text-primary">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Necesidad -->
    <div class="modal fade" id="modalEditarNecesidad" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Modificar Necesidad
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formEditarNecesidad">
                    <div class="modal-body">
                        <input type="hidden" id="edit_necesidad_id">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Nota:</strong> Solo se modificará la información de la necesidad, no las relaciones.
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><strong>Campo a modificar:</strong></label>
                                <select class="form-control" id="edit_necesidad_campo" required>
                                    <option value="">Seleccionar...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><strong>Nuevo Valor:</strong></label>
                                <input type="text" class="form-control" id="edit_necesidad_valor">
                                <small class="text-muted">
                                    Deja vacío o escribe "null" para borrar este campo
                                </small>
                                <small class="form-text text-muted" id="edit_necesidad_hint"></small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6><i class="fas fa-database"></i> Valor Actual:</h6>
                                        <p id="edit_necesidad_valor_actual" class="mb-0 font-weight-bold text-primary">-
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Editar Resultado -->
    <div class="modal fade" id="modalEditarResultado" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Modificar Resultado
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="formEditarResultado">
                    <div class="modal-body">
                        <input type="hidden" id="edit_resultado_id">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Nota:</strong> Solo se modificarán los datos del resultado.
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><strong>Campo a modificar:</strong></label>
                                <select class="form-control" id="edit_resultado_campo" required>
                                    <option value="">Seleccionar...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><strong>Nuevo Valor:</strong></label>
                                <input type="text" class="form-control" id="edit_resultado_valor">
                                <small class="text-muted">
                                    Deja vacío o escribe "null" para borrar este campo
                                </small>
                                <small class="form-text text-muted" id="edit_resultado_hint"></small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6><i class="fas fa-database"></i> Valor Actual:</h6>
                                        <p id="edit_resultado_valor_actual" class="mb-0 font-weight-bold text-primary">-
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        // Configuración CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Variables globales
        let contratoActual = null;
        let necesidadesActuales = [];
        let resultadosActuales = [];

        // Campos editables (sin relaciones)
        const camposContrato = {
            'orden_servicio': 'Orden de Servicio',
            'objeto': 'Objeto',
            'desc_general_act': 'Descripción General',
            'tiempo_ejecucion': 'Tiempo de Ejecución',
            'fecha_inicio': 'Fecha de Inicio',
            'canales': 'Canales',
            'interventor': 'Interventor',
            'administrador': 'Administrador',
            'dotacion': 'Dotación',
            'alimentacion': 'Alimentación',
            'transporte': 'Transporte',
            'horario': 'Horario',
            'requisitos': 'Requisitos',
            'forma_fecha': 'Forma y Fecha',
            'observaciones': 'Observaciones',
            'obs_proceso': 'Observaciones del Proceso',
            'status': 'Estado',
            'moderation_status': 'Estado de Moderación'
        };

        const camposNecesidad = {
            'nombre': 'Nombre',
            'tipo_unidad': 'Tipo de Unidad',
            'total': 'Total',
            'empresa': 'Empresa',
            'local': 'Local',
            'descripcion': 'Descripción',
            'vacante': 'Vacante',
            'tipo_requerimiento': 'Tipo de Requerimiento',
            'tipo_salario': 'Tipo de Salario',
            'tipo_contrato': 'Tipo de Contrato',
            'pruebas': 'Pruebas',
            'examenes': 'Exámenes',
            'status': 'Estado',
            'moderation_status': 'Estado de Moderación'
        };

        const camposResultado = {
            'r_empresa': 'Empresa',
            'r_nit': 'NIT',
            'r_municipio': 'Municipio',
            'r_observaciones': 'Observaciones',
            'r_codigo_vacante': 'Código de Vacante',
            'r_codigo_certi_resi': 'Código Certificado de Residencia',
            'r_prestador_spe': 'Prestador SPE',
            'r_postulados': 'Postulados'
        };

        // Función para mostrar alertas
        function mostrarAlerta(mensaje, tipo = 'success') {
            const alertHtml = `
                <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'danger' ? 'exclamation-triangle' : 'info-circle'}"></i>
                    ${mensaje}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;
            document.getElementById('alertContainer').innerHTML = alertHtml;

            setTimeout(() => {
                $('.alert').alert('close');
            }, 5000);
        }

        // 1. Buscar Contrato
        document.getElementById('formBuscarContrato').addEventListener('submit', async function(e) {
            e.preventDefault();

            const contratoId = document.getElementById('contrato_id').value;

            try {
                const response = await fetch('correcciones/buscar-contrato', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        contrato_id: contratoId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    contratoActual = data.contrato;
                    necesidadesActuales = data.necesidades;

                    mostrarDatosContrato(data.contrato);
                    mostrarNecesidades(data.necesidades);
                    mostrarAlerta('Contrato encontrado correctamente', 'success');
                } else {
                    mostrarAlerta(data.message, 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarAlerta('Error al buscar el contrato', 'danger');
            }
        });

        // Mostrar datos del contrato
        function mostrarDatosContrato(contrato) {
            const tbody = document.getElementById('datosContrato');
            let html = '<tr><th width="200">ID Orden:</th><td>' + contrato.id + '</td></tr>';
            html += '<tr><th>Contrato ID:</th><td><strong class="text-primary">' + contrato.contrato_id +
                '</strong></td></tr>';

            // Mostrar algunos campos importantes
            if (contrato.orden_servicio) html += '<tr><th>Orden Servicio:</th><td>' + contrato.orden_servicio +
                '</td></tr>';
            if (contrato.objeto) html += '<tr><th>Objeto:</th><td>' + contrato.objeto + '</td></tr>';
            if (contrato.status) html += '<tr><th>Estado:</th><td><span class="badge badge-info">' + contrato.status +
                '</span></td></tr>';

            tbody.innerHTML = html;
            document.getElementById('resultadosContrato').style.display = 'block';
        }

        // Mostrar necesidades
        function mostrarNecesidades(necesidades) {
            const tbody = document.getElementById('tablaNecesidades');
            tbody.innerHTML = '';

            if (necesidades.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No se encontraron necesidades</td></tr>';
            } else {
                necesidades.forEach(necesidad => {
                    const row = `
                        <tr>
                            <td>${necesidad.id}</td>
                            <td>${necesidad.contrato_id}</td>
                            <td>${necesidad.orden_id || 'N/A'}</td>
                            <td>${necesidad.nombre || 'N/A'}</td>
                            <td>${necesidad.local || 'N/A'}</td>
                            <td><span class="badge badge-${necesidad.status === 'publicado' ? 'success' : 'warning'}">${necesidad.status || 'N/A'}</span></td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="editarNecesidad(${necesidad.id})">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-info" onclick="verResultados(${necesidad.id})">
                                    <i class="fas fa-eye"></i> Resultados
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            document.getElementById('seccionNecesidades').style.display = 'block';
        }

        // Abrir modal editar contrato
        function abrirModalEditarContrato() {
            document.getElementById('edit_orden_id').value = contratoActual.id;
            document.getElementById('edit_orden_id_display').value = contratoActual.id;

            // Llenar select con campos
            const select = document.getElementById('edit_contrato_campo');
            select.innerHTML = '<option value="">Seleccionar...</option>';

            for (const [campo, nombre] of Object.entries(camposContrato)) {
                select.innerHTML += `<option value="${campo}">${nombre}</option>`;
            }

            // Limpiar valores
            document.getElementById('edit_contrato_valor').value = '';
            document.getElementById('edit_contrato_valor_actual').textContent = '-';
            document.getElementById('edit_contrato_hint').textContent = '';

            $('#modalEditarContrato').modal('show');
        }

        // Cambio de campo en modal de contrato
        document.getElementById('edit_contrato_campo')?.addEventListener('change', function() {
            const campo = this.value;
            if (campo && contratoActual) {
                const valorActual = contratoActual[campo] || 'Sin valor';
                document.getElementById('edit_contrato_valor_actual').textContent = valorActual;
                document.getElementById('edit_contrato_hint').textContent = `Actual: ${valorActual}`;
            }
        });

        // Actualizar Contrato
        document.getElementById('formEditarContrato').addEventListener('submit', async function(e) {
            e.preventDefault();

            const campo = document.getElementById('edit_contrato_campo').value;
            const valor = document.getElementById('edit_contrato_valor').value;
            const ordenId = document.getElementById('edit_orden_id').value;

            if (!campo) {
                mostrarAlerta('Debe seleccionar un campo', 'danger');
                return;
            }

            // Si el usuario dejó el campo vacío o escribió "null", enviamos null
            if (valor === '' || valor.toLowerCase() === 'null') {
                valor = null;
            }

            try {
                const response = await fetch('correcciones/actualizar-contrato', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        id: ordenId,
                        campo: campo,
                        valor: valor
                    })
                });

                const result = await response.json();

                if (result.success) {
                    mostrarAlerta('Contrato actualizado correctamente', 'success');
                    $('#modalEditarContrato').modal('hide');

                    // Recargar datos
                    document.getElementById('contrato_id').value = ordenId;
                    document.getElementById('formBuscarContrato').dispatchEvent(new Event('submit'));
                } else {
                    mostrarAlerta(result.message, 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarAlerta('Error al actualizar el contrato', 'danger');
            }
        });

        // Editar Necesidad
        function editarNecesidad(necesidadId) {
            const necesidad = necesidadesActuales.find(n => n.id === necesidadId);

            if (!necesidad) {
                mostrarAlerta('Necesidad no encontrada', 'danger');
                return;
            }

            document.getElementById('edit_necesidad_id').value = necesidadId;

            // Llenar select con campos
            const select = document.getElementById('edit_necesidad_campo');
            select.innerHTML = '<option value="">Seleccionar...</option>';

            for (const [campo, nombre] of Object.entries(camposNecesidad)) {
                select.innerHTML += `<option value="${campo}">${nombre}</option>`;
            }

            // Limpiar valores
            document.getElementById('edit_necesidad_valor').value = '';
            document.getElementById('edit_necesidad_valor_actual').textContent = '-';
            document.getElementById('edit_necesidad_hint').textContent = '';

            // Guardar necesidad actual para mostrar valores
            window.necesidadActualEdit = necesidad;

            $('#modalEditarNecesidad').modal('show');
        }

        // Cambio de campo en modal de necesidad
        document.getElementById('edit_necesidad_campo')?.addEventListener('change', function() {
            const campo = this.value;
            if (campo && window.necesidadActualEdit) {
                const valorActual = window.necesidadActualEdit[campo] || 'Sin valor';
                document.getElementById('edit_necesidad_valor_actual').textContent = valorActual;
                document.getElementById('edit_necesidad_hint').textContent = `Actual: ${valorActual}`;
            }
        });

        document.getElementById('formEditarNecesidad').addEventListener('submit', async function(e) {
            e.preventDefault();

            const campo = document.getElementById('edit_necesidad_campo').value;
            const valor = document.getElementById('edit_necesidad_valor').value;
            const necesidadId = document.getElementById('edit_necesidad_id').value;

            if (!campo) {
                mostrarAlerta('Debe seleccionar un campo', 'danger');
                return;
            }

            // Si el usuario dejó el campo vacío o escribió "null", enviamos null
            if (valor === '' || valor.toLowerCase() === 'null') {
                valor = null;
            }

            try {
                const response = await fetch('correcciones/actualizar-necesidad', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        necesidad_id: necesidadId,
                        campo: campo,
                        valor: valor
                    })
                });

                const result = await response.json();

                if (result.success) {
                    mostrarAlerta('Necesidad actualizada correctamente', 'success');
                    $('#modalEditarNecesidad').modal('hide');

                    // Recargar necesidades
                    document.getElementById('formBuscarContrato').dispatchEvent(new Event('submit'));
                } else {
                    mostrarAlerta(result.message, 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarAlerta('Error al actualizar la necesidad', 'danger');
            }
        });

        // Ver Resultados de Necesidad
        async function verResultados(necesidadId) {
            try {
                const response = await fetch('correcciones/obtener-resultados', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        necesidad_id: necesidadId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    resultadosActuales = data.resultados;
                    mostrarResultados(data.resultados);
                } else {
                    mostrarAlerta(data.message, 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarAlerta('Error al obtener resultados', 'danger');
            }
        }

        function mostrarResultados(resultados) {
            const tbody = document.getElementById('tablaResultados');
            tbody.innerHTML = '';

            if (resultados.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No se encontraron resultados</td></tr>';
            } else {
                resultados.forEach(resultado => {
                    const row = `
                        <tr>
                            <td>${resultado.id}</td>
                            <td>${resultado.necesidad_id}</td>
                            <td>${resultado.r_empresa || 'N/A'}</td>
                            <td>${resultado.r_nit || 'N/A'}</td>
                            <td>${resultado.r_municipio || 'N/A'}</td>
                            <td>${resultado.r_observaciones || 'N/A'}</td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="editarResultado(${resultado.id})">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            }

            document.getElementById('seccionResultados').style.display = 'block';
        }

        // Editar Resultado
        function editarResultado(resultadoId) {
            const resultado = resultadosActuales.find(r => r.id === resultadoId);

            if (!resultado) {
                mostrarAlerta('Resultado no encontrado', 'danger');
                return;
            }

            document.getElementById('edit_resultado_id').value = resultadoId;

            // Llenar select con campos
            const select = document.getElementById('edit_resultado_campo');
            select.innerHTML = '<option value="">Seleccionar...</option>';

            for (const [campo, nombre] of Object.entries(camposResultado)) {
                select.innerHTML += `<option value="${campo}">${nombre}</option>`;
            }

            // Limpiar valores
            document.getElementById('edit_resultado_valor').value = '';
            document.getElementById('edit_resultado_valor_actual').textContent = '-';
            document.getElementById('edit_resultado_hint').textContent = '';

            // Guardar resultado actual
            window.resultadoActualEdit = resultado;

            $('#modalEditarResultado').modal('show');
        }

        // Cambio de campo en modal de resultado
        document.getElementById('edit_resultado_campo')?.addEventListener('change', function() {
            const campo = this.value;
            if (campo && window.resultadoActualEdit) {
                const valorActual = window.resultadoActualEdit[campo] || 'Sin valor';
                document.getElementById('edit_resultado_valor_actual').textContent = valorActual;
                document.getElementById('edit_resultado_hint').textContent = `Actual: ${valorActual}`;
            }
        });

        document.getElementById('formEditarResultado').addEventListener('submit', async function(e) {
            e.preventDefault();

            const campo = document.getElementById('edit_resultado_campo').value;
            const valor = document.getElementById('edit_resultado_valor').value;
            const resultadoId = document.getElementById('edit_resultado_id').value;

            if (!campo) {
                mostrarAlerta('Debe seleccionar un campo', 'danger');
                return;
            }

            // Si el usuario dejó el campo vacío o escribió "null", enviamos null
            if (valor === '' || valor.toLowerCase() === 'null') {
                valor = null;
            }

            try {
                const response = await fetch('correcciones/actualizar-resultado', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        resultado_id: resultadoId,
                        campo: campo,
                        valor: valor
                    })
                });

                const result = await response.json();

                if (result.success) {
                    mostrarAlerta('Resultado actualizado correctamente', 'success');
                    $('#modalEditarResultado').modal('hide');

                    // Recargar resultados
                    const necesidadId = window.resultadoActualEdit.necesidad_id;
                    verResultados(necesidadId);
                } else {
                    mostrarAlerta(result.message, 'danger');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarAlerta('Error al actualizar el resultado', 'danger');
            }
        });
    </script>
@endsection
