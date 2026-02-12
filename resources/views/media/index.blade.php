@extends('adminlte::page')

@section('title', 'Biblioteca de Medios')

@section('content_header')
    <div class="row">
        <div class="col-sm-6">
            <h1>Biblioteca de Medios</h1>
        </div>
        <div class="col-sm-6">
            <button type="button" class="btn btn-primary float-right" data-toggle="modal" data-target="#uploadModal">
                <i class="fas fa-upload"></i> Subir Archivos
            </button>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-4">
                    <form method="GET" action="{{ route('media.index') }}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Buscar medios..." value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-control" id="typeFilter">
                        <option value="">Todos los tipos</option>
                        <option value="image" {{ request('type') == 'image' ? 'selected' : '' }}>Imágenes</option>
                        <option value="video" {{ request('type') == 'video' ? 'selected' : '' }}>Videos</option>
                        <option value="audio" {{ request('type') == 'audio' ? 'selected' : '' }}>Audio</option>
                        <option value="document" {{ request('type') == 'document' ? 'selected' : '' }}>Documentos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="month" class="form-control" id="monthFilter">
                        <option value="">Todas las fechas</option>
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-block" id="bulkDeleteBtn" style="display:none;">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($media->isEmpty())
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay medios disponibles</h4>
                    <p>Sube tu primer archivo para comenzar</p>
                </div>
            @else
                <div class="row" id="mediaGrid">
                    @foreach($media as $item)
                        <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-4">
                            <div class="media-item" data-id="{{ $item->id }}">
                                <div class="card h-100">
                                    <div class="card-header p-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input media-checkbox" id="check{{ $item->id }}" value="{{ $item->id }}">
                                            <label class="custom-control-label" for="check{{ $item->id }}"></label>
                                        </div>
                                    </div>
                                    <div class="card-body p-2 text-center media-preview" data-toggle="modal" data-target="#mediaModal{{ $item->id }}" style="cursor: pointer;">
                                        @if($item->type == 'image')
                                            <img src="{{ $item->url }}" alt="{{ $item->name }}" class="img-fluid" style="max-height: 120px; object-fit: cover;">
                                        @else
                                            <div class="py-4">
                                                <i class="{{ $item->icon_class }} fa-3x"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="card-footer p-2">
                                        <small class="d-block text-truncate" title="{{ $item->name }}">
                                            {{ $item->name }}
                                        </small>
                                        <small class="text-muted">{{ $item->human_readable_size }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal de detalle -->
                        <div class="modal fade" id="mediaModal{{ $item->id }}" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Detalles del Medio</h5>
                                        <button type="button" class="close" data-dismiss="modal">
                                            <span>&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                @if($item->type == 'image')
                                                    <img src="{{ $item->url }}" alt="{{ $item->name }}" class="img-fluid">
                                                @elseif($item->type == 'video')
                                                    <video controls class="w-100">
                                                        <source src="{{ $item->url }}" type="{{ $item->mime_type }}">
                                                    </video>
                                                @elseif($item->type == 'audio')
                                                    <audio controls class="w-100">
                                                        <source src="{{ $item->url }}" type="{{ $item->mime_type }}">
                                                    </audio>
                                                @else
                                                    <div class="text-center py-5">
                                                        <i class="{{ $item->icon_class }} fa-5x"></i>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col-md-6">
                                                <form id="updateForm{{ $item->id }}" class="mb-3">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="form-group">
                                                        <label>Nombre</label>
                                                        <input type="text" name="name" class="form-control" value="{{ $item->name }}">
                                                    </div>
                                                    <button type="submit" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-save"></i> Actualizar
                                                    </button>
                                                </form>

                                                <table class="table table-sm">
                                                    <tr>
                                                        <th>Nombre de archivo:</th>
                                                        <td>{{ $item->file_name }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Tipo:</th>
                                                        <td>{{ $item->mime_type }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Tamaño:</th>
                                                        <td>{{ $item->human_readable_size }}</td>
                                                    </tr>
                                                    @if($item->metadata && isset($item->metadata['width']))
                                                        <tr>
                                                            <th>Dimensiones:</th>
                                                            <td>{{ $item->metadata['width'] }} × {{ $item->metadata['height'] }} px</td>
                                                        </tr>
                                                    @endif
                                                    <tr>
                                                        <th>Subido:</th>
                                                        <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Por:</th>
                                                        <td>{{ $item->user->name ?? 'Sistema' }}</td>
                                                    </tr>
                                                </table>

                                                <div class="form-group">
                                                    <label>URL del archivo:</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" value="{{ $item->url }}" id="url{{ $item->id }}" readonly>
                                                        <div class="input-group-append">
                                                            <button class="btn btn-outline-secondary copy-url" type="button" data-url="{{ $item->url }}">
                                                                <i class="fas fa-copy"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="btn-group w-100">
                                                    <a href="{{ route('media.download', $item) }}" class="btn btn-success">
                                                        <i class="fas fa-download"></i> Descargar
                                                    </a>
                                                    <a href="{{ $item->url }}" target="_blank" class="btn btn-info">
                                                        <i class="fas fa-external-link-alt"></i> Ver
                                                    </a>
                                                    <button type="button" class="btn btn-danger delete-media" data-id="{{ $item->id }}">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="row">
                    <div class="col-12">
                        {{ $media->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal de subida -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Subir Archivos</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Seleccionar archivos</label>
                            <div class="custom-file">
                                <input type="file" name="files[]" class="custom-file-input" id="fileInput" multiple>
                                <label class="custom-file-label" for="fileInput">Elegir archivos...</label>
                            </div>
                            <small class="form-text text-muted">Máximo 50MB por archivo</small>
                        </div>
                        <div id="filePreview" class="row"></div>
                        <div class="progress" style="display:none;" id="uploadProgress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <i class="fas fa-upload"></i> Subir Archivos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    .media-item .card {
        transition: all 0.3s;
        cursor: pointer;
    }
    .media-item .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .media-item .card-header {
        background: transparent;
        border-bottom: 1px solid #dee2e6;
    }
    .media-preview img {
        transition: transform 0.3s;
    }
    .media-preview:hover img {
        transform: scale(1.05);
    }
    .custom-file-label::after {
        content: "Buscar";
    }
</style>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Filtros
    $('#typeFilter, #monthFilter').change(function() {
        let url = new URL(window.location.href);
        let type = $('#typeFilter').val();
        let month = $('#monthFilter').val();

        if (type) url.searchParams.set('type', type);
        else url.searchParams.delete('type');

        if (month) url.searchParams.set('month', month);
        else url.searchParams.delete('month');

        window.location.href = url.toString();
    });

    // Selección múltiple
    let selectedItems = [];
    $('.media-checkbox').change(function() {
        if ($(this).is(':checked')) {
            selectedItems.push($(this).val());
        } else {
            selectedItems = selectedItems.filter(id => id !== $(this).val());
        }

        if (selectedItems.length > 0) {
            $('#bulkDeleteBtn').show();
        } else {
            $('#bulkDeleteBtn').hide();
        }
    });

    // Eliminación masiva
    $('#bulkDeleteBtn').click(function() {
        if (confirm('¿Estás seguro de eliminar ' + selectedItems.length + ' medio(s)?')) {
            $.ajax({
                url: '{{ route("media.bulk-delete") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: selectedItems
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    alert('Error al eliminar medios');
                }
            });
        }
    });

    // Preview de archivos seleccionados
    $('#fileInput').change(function() {
        let files = this.files;
        let preview = $('#filePreview');
        preview.empty();

        $('.custom-file-label').text(files.length + ' archivo(s) seleccionado(s)');

        for (let i = 0; i < files.length; i++) {
            let file = files[i];
            let reader = new FileReader();

            reader.onload = function(e) {
                let col = $('<div class="col-md-3 mb-3"></div>');
                let card = $('<div class="card"></div>');
                let body = $('<div class="card-body p-2 text-center"></div>');

                if (file.type.startsWith('image/')) {
                    body.append('<img src="' + e.target.result + '" class="img-fluid" style="max-height: 100px;">');
                } else {
                    body.append('<i class="fas fa-file fa-3x"></i>');
                }

                body.append('<small class="d-block mt-2 text-truncate">' + file.name + '</small>');
                card.append(body);
                col.append(card);
                preview.append(col);
            };

            reader.readAsDataURL(file);
        }
    });

    // Upload AJAX
    $('#uploadForm').submit(function(e) {
        e.preventDefault();

        let formData = new FormData(this);
        let progressBar = $('#uploadProgress');
        let progressBarInner = progressBar.find('.progress-bar');

        progressBar.show();
        $('#uploadBtn').prop('disabled', true);

        $.ajax({
            url: '{{ route("media.upload") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                let xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        let percentComplete = (e.loaded / e.total) * 100;
                        progressBarInner.css('width', percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                $('#uploadModal').modal('hide');
                location.reload();
            },
            error: function(xhr) {
                alert('Error al subir archivos');
                progressBar.hide();
                $('#uploadBtn').prop('disabled', false);
            }
        });
    });

    // Actualizar nombre
    $('[id^="updateForm"]').submit(function(e) {
        e.preventDefault();

        let form = $(this);
        let mediaId = form.attr('id').replace('updateForm', '');

        $.ajax({
            url: '/media/' + mediaId,
            method: 'PUT',
            data: form.serialize(),
            success: function(response) {
                alert('Medio actualizado correctamente');
                location.reload();
            },
            error: function(xhr) {
                alert('Error al actualizar');
            }
        });
    });

    // Eliminar medio
    $('.delete-media').click(function() {
        if (confirm('¿Estás seguro de eliminar este medio?')) {
            let mediaId = $(this).data('id');

            $.ajax({
                url: '/media/' + mediaId,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    alert('Error al eliminar');
                }
            });
        }
    });

    // Copiar URL
    $('.copy-url').click(function() {
        let url = $(this).data('url');
        let temp = $('<input>');
        $('body').append(temp);
        temp.val(url).select();
        document.execCommand('copy');
        temp.remove();

        $(this).html('<i class="fas fa-check"></i>');
        setTimeout(() => {
            $(this).html('<i class="fas fa-copy"></i>');
        }, 2000);
    });
});
</script>
@stop
