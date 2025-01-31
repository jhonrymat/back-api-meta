@extends('adminlte::page')
@section('title', 'Editar Plantilla de Correo')

@section('content_header')
    <h1>Editar Plantilla de Correo</h1>
@stop

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
                        <form action="{{ route('email-templates.update', $emailTemplate->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="form-group mb-3">
                                <label for="name">Nombre de la Plantilla</label>
                                <input type="text" name="name" id="name" class="form-control"
                                    value="{{ old('name', $emailTemplate->name) }}" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="card_background_color">Color de Fondo de la Tarjeta</label>
                                <input type="color" name="card_background_color" id="card_background_color"
                                    class="form-control"
                                    value="{{ old('card_background_color', $emailTemplate->card_background_color ?? '#ffffff') }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="header_color">Color del Encabezado</label>
                                <input type="color" name="header_color" id="header_color" class="form-control"
                                    value="{{ old('header_color', $emailTemplate->header_color) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="footer_color">Color del Pie de Página</label>
                                <input type="color" name="footer_color" id="footer_color" class="form-control"
                                    value="{{ old('footer_color', $emailTemplate->footer_color) }}">
                            </div>

                            <div class="form-group mb-3">
                                <label for="title">Título Principal</label>
                                <textarea name="title" id="title" class="form-control summernote">{{ old('title', $emailTemplate->title) }}</textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="logo">Logo</label>
                                <input type="file" name="logo" id="logo" class="form-control">
                                @if ($emailTemplate->logo)
                                    <div class="mt-2">
                                        <img id="existing-logo" src="{{ Storage::url($emailTemplate->logo) }}"
                                            alt="Logo Actual" style="max-width: 100px;">
                                    </div>
                                @endif
                            </div>

                            <div class="form-group mb-3">
                                <label for="html_content">Contenido del Correo</label>
                                <textarea name="html_content" id="html_content" class="form-control summernote">{{ old('html_content', $emailTemplate->html_content) }}</textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="footer_text">Texto del Pie de Página</label>
                                <textarea name="footer_text" id="footer_text" class="form-control summernote">{{ old('footer_text', $emailTemplate->footer_text) }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-success">Actualizar Plantilla</button>
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
                                <img id="preview-logo" src="{{ Storage::url($emailTemplate->logo) }}" alt="Logo"
                                    style="max-width: 150px; margin-bottom: 10px;">
                            </div>
                            <div id="preview-header"
                                style="background-color: {{ $emailTemplate->header_color }}; padding: 10px;">
                                <div id="preview-title">{!! $emailTemplate->title !!}</div>
                            </div>
                            <div id="preview-body" style="padding: 20px;">
                                <div id="preview-content">{!! $emailTemplate->html_content !!}</div>
                            </div>
                            <div id="preview-footer"
                                style="background-color: {{ $emailTemplate->footer_color }}; padding: 10px;">
                                <div id="preview-footer-text">{!! $emailTemplate->footer_text !!}</div>
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
        </style>
    @endsection

    @section('js')
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
        <script>
            $(document).ready(function() {
                // Inicializar Summernote para cada campo
                $('#title').summernote({
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
                        onChange: function(contents) {
                            $('#preview-title').html(contents); // Actualizar título
                        },
                    },
                });

                $('#html_content').summernote({
                    height: 200,
                    callbacks: {
                        onChange: function(contents) {
                            $('#preview-content').html(contents); // Actualizar contenido del correo
                        },
                    },
                });

                $('#footer_text').summernote({
                    height: 200,
                    callbacks: {
                        onChange: function(contents) {
                            $('#preview-footer-text').html(contents); // Actualizar texto del pie de página
                        },
                    },
                });

                // Actualizar colores en tiempo real
                $('#card_background_color').on('input', function() {
                    $('#preview-container').css('background-color', $(this).val());
                });

                $('#header_color').on('input', function() {
                    $('#preview-header').css('background-color', $(this).val());
                });

                $('#footer_color').on('input', function() {
                    $('#preview-footer').css('background-color', $(this).val());
                });

                // Vista previa del logo en tiempo real
                $('#logo').on('change', function(event) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#preview-logo').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(event.target.files[0]);
                });

                // Aplicar colores iniciales en la carga
                $('#preview-container').css('background-color', $('#card_background_color').val());
                $('#preview-header').css('background-color', $('#header_color').val());
                $('#preview-footer').css('background-color', $('#footer_color').val());
            });
        </script>
    @endsection
