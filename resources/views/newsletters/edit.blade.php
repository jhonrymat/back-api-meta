@extends('adminlte::page')
@section('title', 'Editar Boletín')
@section('content')
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-white">
                <h2 class="mb-0">Editar Boletín</h2>
            </div>
            <div class="card-body">
                <form action="{{ route('newsletters.update', $newsletter->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="form-group mb-3">
                        <label for="name">Nombre del Boletín</label>
                        <input type="text" name="name" id="name" class="form-control"
                            value="{{ $newsletter->name }}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="subject">Asunto</label>
                        <input type="text" name="subject" id="subject" class="form-control"
                            value="{{ $newsletter->subject }}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="copy_email">Email de Copia (Opcional)</label>
                        <input type="email" name="copy_email" id="copy_email" class="form-control"
                            value="{{ $newsletter->copy_email }}">
                    </div>
                    <div class="form-group mb-3">
                        <label for="attachment">Archivo Adjunto (PDF Opcional)</label>
                        @if ($newsletter->attachment_path)
                            <p>Archivo actual: <a href="{{ $newsletter->attachment_url }}" target="_blank">Ver archivo</a>
                            </p>
                        @endif
                        <input type="file" name="attachment" id="attachment" class="form-control"
                            accept="application/pdf">
                    </div>
                    {{-- Seleccionar Grupos --}}
                    <div class="form-group mb-3">
                        <label for="groups">Seleccionar Grupo(s)</label>
                        <select name="groups[]" id="groups" class="form-control" multiple>
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}"
                                    {{ $newsletter->groups->contains($group->id) ? 'selected' : '' }}>
                                    {{ $group->name }} ({{ $group->userEmails->count() }} usuarios)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Seleccionar Etiquetas --}}
                    <div class="form-group mb-3">
                        <label for="tags">Seleccionar Etiqueta(s)</label>
                        <select name="tags[]" id="tags" class="form-control" multiple>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}"
                                    {{ $newsletter->tags->contains($tag->id) ? 'selected' : '' }}>
                                    {{ $tag->nombre }} ({{ $tag->contactos->count() }} contactos)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Contenido --}}
                    <div class="form-group mb-3">
                        <label for="content">Contenido</label>
                        <textarea name="content" id="content" class="form-control" rows="10">{{ $newsletter->content }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css">
@endsection
@section('js')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#content').summernote({
                placeholder: 'Escribe el contenido del boletín aquí...',
                tabsize: 2,
                height: 300,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'table', 'hr']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
        });
    </script>
@endsection
