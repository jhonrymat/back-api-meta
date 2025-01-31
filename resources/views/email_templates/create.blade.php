@extends('adminlte::page')
@section('title', 'Crear Plantilla de Correo')

@section('content')
    <div class="container">
        <div class="row">
            <!-- Sección Izquierda: Formulario -->
            <div class="col-md-6">
                <div class="card shadow-sm config-container">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">Configurar Plantilla</h2>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('email-templates.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="name">Nombre de la Plantilla</label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="card_background_color">Color de Fondo de la Tarjeta</label>
                                <input type="color" name="card_background_color" id="card_background_color"
                                    class="form-control" value="#ffffff">
                            </div>

                            <div class="form-group mb-3">
                                <label for="header_color">Color del Encabezado</label>
                                <input type="color" name="header_color" id="header_color" class="form-control"
                                    value="#12b5ec">
                            </div>

                            <div class="form-group mb-3">
                                <label for="footer_color">Color del Pie de Página</label>
                                <input type="color" name="footer_color" id="footer_color" class="form-control"
                                    value="#f1f1f1">
                            </div>

                            <div class="form-group mb-3">
                                <label for="title">Título Principal</label>
                                <textarea name="title" id="title" class="form-control summernote"></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="logo">Logo</label>
                                <input type="file" name="logo" id="logo" class="form-control">
                            </div>

                            <div class="form-group mb-3">
                                <label for="html_content">Contenido del Correo</label>
                                <textarea name="html_content" id="html_content" class="form-control summernote"></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="footer_text">Texto del Pie de Página</label>
                                <textarea name="footer_text" id="footer_text" class="form-control summernote"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">Guardar Plantilla</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sección Derecha: Vista Previa en Tiempo Real -->
            <div class="col-md-6">
                <div class="card shadow-sm preview-container">
                    <div class="card-header bg-secondary text-white">
                        <h2 class="mb-0">Vista Previa</h2>
                    </div>
                    <div class="card-body">
                        <div id="preview-container">
                            <div style="text-align: center;">
                                <img id="preview-logo" src="https://via.placeholder.com/150" alt="Logo"
                                    style="max-width: 150px; margin-bottom: 10px;">
                            </div>
                            <div id="preview-header"
                                style="background-color: #12b5ec; color: white; text-align: center; padding: 15px;">
                                <div id="preview-title">Título del Correo</div>
                            </div>
                            <div id="preview-body" style="padding: 20px; color: #333;">
                                <div id="preview-content">Aquí se mostrará el contenido del correo.</div>
                            </div>
                            <div id="preview-footer"
                                style="background-color: #f1f1f1; text-align: center; padding: 10px; font-size: 0.9em; color: #555;">
                                <p id="preview-footer-text">
                                    En caso de que usted no desee recibir más información de este tipo, haga clic en
                                    <a href="#" style="color: #d9534f;">Cancelar suscripción</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @endsection

    @section('css')
        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
        <style>
            .config-container {
                max-height: 80vh;
                overflow-y: auto;
                padding-right: 15px;
            }

            .preview-container {
                max-height: 80vh;
                overflow-y: auto;
                padding: 15px;
                background-color: #ffffff;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
        </style>
    @endsection

    @section('js')
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
        <script>
            $(document).ready(function() {
                $('.summernote').summernote({
                    height: 200,
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['strikethrough', 'superscript', 'subscript']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link', 'table', 'hr']],
                        ['view', ['codeview', 'help']]
                    ],
                    callbacks: {
                        onChange: function(contents, $editable) {
                            let id = $editable.closest('.note-editor').prev().attr('id');
                            if (id === 'title') {
                                $('#preview-title').html(contents);
                            }
                            if (id === 'html_content') {
                                $('#preview-content').html(contents);
                            }
                            if (id === 'footer_text') {
                                $('#preview-footer-text').html(contents);
                            }
                        }
                    }
                });

                $('#card_background_color').on('input', function() {
                    $('#preview-container').css('background-color', $(this).val());
                });

                $('#header_color').on('input', function() {
                    $('#preview-header').css('background-color', $(this).val());
                });

                $('#footer_color').on('input', function() {
                    $('#preview-footer').css('background-color', $(this).val());
                });

                $('#logo').on('change', function(event) {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        $('#preview-logo').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(event.target.files[0]);
                });
            });
        </script>
    @endsection
