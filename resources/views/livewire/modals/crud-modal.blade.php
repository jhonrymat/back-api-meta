<div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" id="sample_form" class="form-horizontal">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Contacto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <br>
                    <div class="modal-body">
                        <span id="form_result"></span>
                        <div class="form-group">
                            <label>Nombre de contacto : </label>
                            <input type="text" name="nombre" id="nombre" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label>Apellido : </label>
                            <input type="text" name="apellido" id="apellido" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label>Correo : </label>
                            <input type="email" name="correo" id="correo" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label>Telefono : </label>
                            <input type="number" name="telefono" id="telefono" class="form-control" />
                        </div>
                        <div class="form-group">
                            <select id="etiqueta" name="etiqueta[]" class="form-select form-control mb-3" multiple
                                required>
                                <option value="">Selecciona una Etiqueta</option>
                                @foreach ($tags as $tag)
                                    <option value="{{ $tag->id }}">{{ $tag->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Notas : </label>
                            <input type="text" name="notas" id="notas" class="form-control" />
                        </div>
                        <!-- Campos personalizados -->
                        <div id="customFieldsContainer">
                            @foreach ($customFields as $field)
                                <div class="form-group">
                                    <label for="custom_field_{{ $field->id }}">{{ $field->name }}</label>
                                    <input type="{{ $field->type }}" name="custom_fields[{{ $field->id }}]"
                                        id="custom_field_{{ $field->id }}" class="form-control"
                                        placeholder="Sin valor definido">
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="action" id="action" value="Edit" />
                        <input type="hidden" name="hidden_id" id="hidden_id" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
                    <button type="submit" name="action_button" id="action_button" value="Actualizar"
                        class="btn btn-info">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>
