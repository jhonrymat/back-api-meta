<div class="modal fade" id="modal-edit-{{ $app->id }}" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Vista previa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editForm-{{ $app->id }}">
                @csrf
                @method('PUT') {{-- Este método será simulado por AJAX --}}
                <div class="modal-body">
                    <br>
                    <div class="card" style="width: 100%;">
                        <div class="card-header bg-primary text-white" align="center">
                            Información de la aplicacion
                        </div>
                        <div class="form-group">
                            <label for="nombre-{{ $app->id }}">Nombre</label>
                            <input type="text" class="form-control" id="nombre-{{ $app->id }}" name="nombre"
                                value="{{ $app->nombre }}" required>
                        </div>
                        <div class="form-group">
                            <label for="descripcion-{{ $app->id }}">Descripción</label>
                            <input type="text" class="form-control" id="descripcion-{{ $app->id }}"
                                name="descripcion" value="{{ $app->descripcion }}" required>
                        </div>
                        <div class="form-group">
                            <label for="color-{{ $app->id }}" class="form-label">Color etiqueta</label>
                            <input type="text" class="form-control" id="color-{{ $app->id }}" name="color"
                                value="{{ $app->color }}" required>
                            <!-- Selector de color -->
                            <input type="color" id="colorPicker-{{ $app->id }}"
                                onchange="updateColorValue('{{ $app->id }}')" value="{{ $app->color }}">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Guardar cambios</button>
                    </div>
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Cerrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
