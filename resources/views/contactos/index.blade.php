@extends('adminlte::page')

@section('title', 'Contactos')

@section('content')
    <div class="row">
        <div class="col-lg-12 my-3">
            <div>
                <a data-toggle="modal" data-target="#createContactoModal" class="btn btn-primary btn-sm mb-2" title="Crear">
                    <i class="fa fa-plus-circle"></i>
                </a>
                <a data-toggle="modal" data-target="#importAppModal" class="btn btn-success btn-sm mb-2" title="Importar">
                    <i class="fa fa-file-import"></i>
                </a>

            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif
    <table id="contactosTable" class="table table-striped tabladatatable dt-responsive" style="width:100%">
        <thead>
            <tr>
                <th>No</th>
                <th>Nombre</th>
                <th>Telefono</th>
                <th>Etiqueta</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody style="text-align: center"></tbody>
    </table>

    @include('contactos.modals.crud-modal')
    @include('contactos.modals.create-modal')
    @include('contactos.modals.delete-modal')
    @include('contactos.modals.import-modal')
@endsection
@section('css')
<link rel="stylesheet" href="//cdn.datatables.net/responsive/2.2.1/css/responsive.bootstrap4.css">
@stop

@section('js')
<script src="//cdn.datatables.net/responsive/2.2.1/js/dataTables.responsive.min.js"></script>
<script src="//cdn.datatables.net/responsive/2.2.1/js/responsive.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#contactosTable').DataTable({
                processing: true,
                serverSide: true, // Aquí se habilita la paginación y búsqueda del lado del servidor
                ajax: '{{ route('contactos.index') }}',
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'nombre'
                    },
                    {
                        data: 'telefono'
                    },
                    {
                        data: 'tags',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });
        });

        $(document).on('click', '.edit', function(event) {
            event.preventDefault();
            var id = $(this).attr('id');
            $('#form_result').html('');



            $.ajax({
                url: "contactos/edit/" + id,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                dataType: "json",
                success: function(data) {
                    $('#nombre').val(data.result.nombre);
                    $('#apellido').val(data.result.apellido);
                    $('#correo').val(data.result.correo);
                    $('#telefono').val(data.result.telefono);
                    $('#notas').val(data.result.notas);
                    $('#hidden_id').val(id);
                    $('.modal-title').text('Editar contacto');
                    $('.editpass').hide();

                    // Seleccionar las etiquetas que ya tiene el contacto
                    var selectedTags = $.map(data.result.tags, function(tag) {
                        return tag.id
                            .toString(); // Convertir a cadena para asegurarse de la comparación de tipos
                    });

                    $('#etiqueta').val(selectedTags);

                    $('#formModal').modal('show');
                },
                error: function(data) {
                    var errors = data.responseJSON;
                    console.log(errors);
                }
            })
        });

        $('#sample_form').on('submit', function(event) {
            event.preventDefault();
            var action_url = '';

            if ($('#action').val() == 'Edit') {
                action_url = "{{ route('contactos.update') }}";
            }

            $.ajax({
                type: 'post',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: action_url,
                data: $(this).serialize(),
                dataType: 'json',
                success: function(data) {

                    var html = '';
                    if (data.errors) {
                        html = '<div class="alert alert-danger">';
                        for (var count = 0; count < data.errors.length; count++) {
                            html += '<p>' + data.errors[count] + '</p>';
                        }
                        html += '</div>';
                    }
                    if (data.success) {
                        html = '<div class="alert alert-success">' + data.success + '</div>';
                        $('#sample_form')[0].reset();
                        $('#contactosTable').DataTable().ajax.reload();
                        $('#formModal').modal('hide');
                        Swal.fire('Contacto editado correctamente', data.success, 'success');
                    }
                    $('#form_result').html(html);
                },
                error: function(data) {
                    var errors = data.responseJSON;
                    console.log(errors);
                }
            });
        });

        var user_id;

        $(document).on('click', '.delete', function() {
            user_id = $(this).attr('id');
            $('#confirmModal').modal('show');
        });

        $('#ok_button').click(function() {
            $.ajax({
                url: "contactos/delete/" + user_id,
                beforeSend: function() {
                    $('#ok_button').text('Deleting...');
                },
                success: function(data) {
                    setTimeout(function() {
                        $('#confirmModal').modal('hide');
                        $('#contactosTable').DataTable().ajax.reload();
                        Swal.fire('Contacto eliminado correctamente', data.message, 'success');
                    }, 2000);
                }
            })
        });
    </script>
    <script>
        // Script para agregar validación personalizada usando JavaScript
        document.getElementById('telefono').addEventListener('input', function() {
            var telefonoInput = this;
            var validacion = /^[0-9]{12}$/;

            if (!validacion.test(telefonoInput.value)) {
                telefonoInput.setCustomValidity('Digita el prefijo del país seguido del numero celular');
            } else {
                telefonoInput.setCustomValidity('');
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#createFormContactos').submit(function(e) {
                e.preventDefault(); // Prevenir la recarga de la página
                var formData = $(this).serialize(); // Serializa los datos del formulario
                // console.log(formData);
                $.ajax({
                    type: "POST",
                    url: "{{ route('contactos.store') }}",
                    data: formData,
                    success: function(response) {
                        $('#contactosTable').DataTable().ajax.reload();
                        $('#createContactoModal').modal('hide');
                        // Resetea el formulario
                        $('#createFormContactos').trigger("reset");
                        Swal.fire('Contacto creado correctamente', response.message, 'success');
                    },
                    error: function(error) {
                        Swal.fire('Error al crear el contacto',
                            'Ha ocurrido un error durante la creacion.',
                            'error');
                    }
                });
            });
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#uploadBtn').click(function(e) {
                e.preventDefault(); // Evitar la recarga de la página

                var fileInput = $('#fileUpload')[0];
                if (fileInput.files.length === 0) {
                    $('#uploadError').text('Por favor, selecciona un archivo.').show();
                    return;
                }

                var formData = new FormData();
                formData.append('file', fileInput.files[0]);

                $.ajax({
                    url: 'upload-contactos',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: formData,
                    processData: false, // Evitar que jQuery procese los datos
                    contentType: false, // Evitar que jQuery establezca el tipo de contenido
                    success: function(response) {
                        console.log(response);
                        location
                            .reload();
                        // Aquí puedes manejar el cierre de tu modal o diálogo y actualizar la vista según sea necesario
                        // Por ejemplo, si estás usando Bootstrap Modal puedes cerrarlo así:
                        // $('#createAppModal').modal('hide');
                        // Y si quieres llamar a otra función para mostrar los datos actualizados puedes hacerlo aquí
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al subir archivo:', error);
                        $('#uploadError').text('Error al subir el archivo.').show();
                        // location
                        //     .reload();
                    }
                });
            });
        });
    </script>


@stop