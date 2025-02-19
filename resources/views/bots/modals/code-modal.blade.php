<div class="modal fade" id="modal-code-{{ $bot->id }}" tabindex="-1" role="dialog" aria-labelledby="modelTitleId" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Incrustar Asistente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Para incrustar el asistente en tu sitio web, copia y pega el siguiente código:</p>

                <!-- Aquí colocamos el bloque de código con fondo oscuro y estilo de código -->
                <div class="bg-dark text-white p-3 rounded" id="code-container-{{ $bot->id }}">
                    <code>&lt;script src="{{ config('app.url') }}/js/chat-embed.js?botId={{ $bot->id }}&botNombre={{ urlencode($bot->nombre) }}"&gt;&lt;/script&gt;</code>
                </div>  

                <!-- Botón para copiar el código -->
                <button class="btn btn-primary mt-3" onclick="copyCode('code-container-{{ $bot->id }}')">Copiar código</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
