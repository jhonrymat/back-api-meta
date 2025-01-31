@extends('adminlte::page')
@section('title', 'Crear Boletín')
@section('content')
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Nuevo Boletín</h2>
            </div>
            <div class="card-body">
                <form action="{{ route('newsletters.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group mb-3">
                        <label for="name">Nombre del Boletín</label>
                        <input type="text" name="name" id="name" class="form-control"
                            placeholder="Nombre del Boletín" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="subject">Asunto</label>
                        <input type="text" name="subject" id="subject" class="form-control"
                            placeholder="Asunto del correo" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="copy_email">Email de Copia (Opcional)</label>
                        <input type="email" name="copy_email" id="copy_email" class="form-control"
                            placeholder="Correo de copia">
                    </div>
                    <div class="form-group mb-3">
                        <label for="attachment">Archivo Adjunto (PDF Opcional)</label>
                        <input type="file" name="attachment" id="attachment" class="form-control"
                            accept="application/pdf">
                    </div>
                    {{-- Seleccionar Grupos --}}
                    <div class="form-group mb-3">
                        <label for="groups">Seleccionar Grupo(s)</label>
                        <select name="groups[]" id="groups" class="form-control" multiple required>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->userEmails->count() }}
                                    usuarios)</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- tags --}}
                    <div class="form-group mb-3">
                        <label for="tags">Seleccionar Etiqueta(s)</label>
                        <select name="tags[]" id="tags" class="form-control" multiple required>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->nombre }} ({{ $tag->contactos->count() }}
                                    usuarios)</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Contenido --}}
                    <div class="form-group mb-3">
                        <label for="content">Contenido del Correo</label>
                        <textarea name="content" id="content" class="form-control summernote" rows="10"
                            placeholder="Escribe aquí el contenido del correo">{{ old('content') }}</textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">Crear Boletín</button>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('css')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
@endsection
@section('js')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.summernote').summernote({
        height: 300, // Altura del editor
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'table', 'hr']],
            ['custom', ['variables']],
            ['view', ['codeview', 'help']]
        ],
        buttons: {
            variables: function (context) {
                const ui = $.summernote.ui;

                // Crear el botón de variables con un menú desplegable bien estructurado
                const button = ui.buttonGroup([
                    ui.button({
                        className: 'dropdown-toggle',
                        contents: '<i class="fas fa-code"></i> Variables <span class="caret"></span>',
                        tooltip: 'Insertar variable',
                        data: { toggle: 'dropdown' }
                    }),
                    ui.dropdown({
                        className: 'dropdown-menu',
                        contents: `
                            <a href="#" class="dropdown-item" data-variable="nombre">nombre</a>
                            <a href="#" class="dropdown-item" data-variable="email">email</a>
                        `,
                        callback: function ($dropdown) {
                            $dropdown.on('click', '.dropdown-item', function (e) {
                                e.preventDefault();
                                const variable = $(this).data('variable');
                                if (variable) {
                                    // Inserta la variable de forma segura en el contenido
                                    context.invoke('editor.insertText', `@{{${variable}}}`);
                                }
                            });
                        }
                    })
                ]);

                return button.render();
            }
        }
    });

            // Corrige el manejo del evento 'wheel' dentro de Summernote
            $('.summernote').on('summernote.init', function() {
                $('.note-editor').on('wheel', function(event) {
                    event.stopPropagation(); // Detiene la propagación del evento 'wheel'
                });
            });
        });

    </script>
@endsection
