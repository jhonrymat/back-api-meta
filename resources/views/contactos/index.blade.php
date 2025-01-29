@extends('adminlte::page')

@section('title', 'Contactos')

@section('content')
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>¡Éxito!</strong> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>¡Error!</strong> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>¡Ups! Algo salió mal:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="row">
            <!-- Importar y Exportar -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        Importar Contactos
                    </div>
                    <div class="card-body">
                        <form action="{{ route('importar-contactos') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <p>Cargue un archivo CSV en el formato indicado. Puede <a
                                    href="{{ route('descargar-plantilla') }}">descargar un ejemplo aquí</a>.</p>
                            <div class="form-group">
                                <input type="file" class="form-control-file" name="file" accept=".csv" required>
                            </div>
                            <button type="submit" class="btn btn-success mt-3">Cargar</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        Exportar Contactos
                    </div>
                    <div class="card-body">
                        <p>Descargue todos los contactos en un archivo CSV.</p>
                        <a href="{{ route('exportar-contactos') }}" class="btn btn-info">Descargar</a>
                    </div>
                </div>
            </div>
            <!-- Crear Nuevo Contacto -->
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Crear Nuevo Contacto
                    </div>
                    <div class="card-body">
                        <form id="createFormContactos" method="POST" action="{{ route('contactos.store') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre">Nombre</label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="correo">Correo</label>
                                        <input type="email" class="form-control" id="correo" name="correo">
                                    </div>
                                    <div class="form-group">
                                        <label for="etiqueta">Etiquetas</label>
                                        <select id="etiqueta" name="etiqueta[]" class="form-select" multiple required>
                                            @foreach ($tags as $tag)
                                                <option value="{{ $tag->id }}">{{ $tag->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="apellido">Apellido</label>
                                        <input type="text" class="form-control" id="apellido" name="apellido">
                                    </div>
                                    <div class="form-group">
                                        <label for="telefono">Teléfono</label>
                                        <input type="tel" class="form-control" id="telefono" name="telefono"
                                            pattern="[0-9]{12}"
                                            title="Digita el prefijo del país seguido del número celular" required>
                                        <small class="form-text text-muted">Ejemplo: 571234567890</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="notas">Notas</label>
                                        {{-- textarea --}}
                                        <textarea class="form-control" id="notas" name="notas"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                @forelse ($customFields as $field)
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="custom_field_{{ $field->id }}">{{ $field->name }}</label>
                                            <input type="{{ $field->type }}" name="custom_fields[{{ $field->id }}]"
                                                id="custom_field_{{ $field->id }}" class="form-control">
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-muted text-center">No hay campos personalizados disponibles.</p>
                                    </div>
                                @endforelse
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
