<div class="modal fade" id="modal-bot-{{ $bot->id }}" tabindex="-1" role="dialog" aria-labelledby="modelTitleId"
    aria-hidden="true">

    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chat con Asistente</h5>
                <button type="button" class="btn btn-secondary btn-sm mr-2 clear-chat" style="margin-left: auto;"
                    data-bot-id="{{ $bot->id }}">Limpiar chat</button>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="chat-box" id="chat-box-{{ $bot->id }}">
                    <div class="chat-message bot-message">
                        <p>¡Hola! ¿Cómo puedo ayudarte hoy?</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row w-100">
                    <div class="col-9">
                        <input type="text" id="user-input-{{ $bot->id }}" class="form-control"
                            placeholder="Escribe un mensaje..." />
                    </div>
                    <div class="col-1 text-center">
                        <!-- Botón con icono FontAwesome -->
                        <label for="image-input-{{ $bot->id }}" class="btn btn-secondary w-100">
                            <i class="fas fa-image"></i> <!-- Icono de imagen -->
                            <input type="file" id="image-input-{{ $bot->id }}" class="d-none"
                                accept="image/*" />
                        </label>
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-primary w-100" id="send-btn-{{ $bot->id }}"
                            data-bot-id="{{ $bot->id }}">Enviar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
