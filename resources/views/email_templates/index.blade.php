@extends('adminlte::page')

@section('title', 'Plantillas de Correo')

@section('content_header')
    <div class="text-white">
        <a href="{{ route('email-templates.create') }}" class="btn btn-success">Crear Plantilla</a>
    </div>
@stop

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Listado de Plantillas de correo</h2>
            </div>
            <div class="card-body">
                @if ($templates->isEmpty())
                    <p class="text-center">No hay plantillas creadas.</p>
                @else
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Colores</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($templates as $template)
                                <tr>
                                    <td>{{ $template->name }}</td>
                                    <td>
                                        <span
                                            style="background-color: {{ $template->header_color }}; padding: 5px; color: white;">Header</span>
                                        <span
                                            style="background-color: {{ $template->background_color }}; padding: 5px; color: black;">Body</span>
                                        <span
                                            style="background-color: {{ $template->footer_color }}; padding: 5px; color: gray;">Footer</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('email-templates.edit', $template->id) }}"
                                            class="btn btn-warning btn-sm">Editar</a>
                                        <form action="{{ route('email-templates.destroy', $template->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                        </form>
                                        <button type="button" class="btn btn-primary btn-sm preview-email"
                                            data-id="{{ $template->id }}" data-bs-toggle="modal"
                                            data-bs-target="#emailPreviewModal">
                                            Previsualizar Correo
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <!-- Modal de Bootstrap -->
                    <div class="modal fade" id="emailPreviewModal" tabindex="-1" aria-labelledby="emailPreviewLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="emailPreviewLabel">Previsualización del Correo</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="emailContent">
                                        <p class="text-center">Cargando contenido...</p>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                @endif
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    {{-- bootstrap cdn --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.preview-email').on('click', function() {
                var templateId = $(this).data('id'); // Obtener el ID del botón clickeado

                // Hacer petición AJAX para obtener los datos de la plantilla seleccionada
                $.ajax({
                    url: "{{ route('email.preview') }}", // Ruta que devuelve la vista
                    method: "GET",
                    data: {
                        id: templateId
                    },
                    success: function(response) {
                        $("#emailContent").html(response); // Cargar la respuesta en el modal
                    },
                    error: function() {
                        $("#emailContent").html(
                            "<p class='text-center text-danger'>Error al cargar la previsualización.</p>"
                            );
                    }
                });
            });
        });
    </script>

@stop
