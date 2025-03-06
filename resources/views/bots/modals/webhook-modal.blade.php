<div class="modal fade" id="webhookModal-{{ $bot->id }}" tabindex="-1"
    aria-labelledby="webhookModalLabel-{{ $bot->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="webhookModalLabel-{{ $bot->id }}">Configurar Webhook</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-3">
                        <label for="webhook_url_{{ $bot->id }}" class="form-label">URL del Webhook</label>
                        <input type="url" class="form-control" id="webhook_url_{{ $bot->id }}"
                            name="webhook_url" value="{{ $bot->webhook_url ?? '' }}" required
                            placeholder="{{ $bot->webhook_url ? '' : 'No hay webhook configurado' }}">
                    </div>
                    <button type="button" class="btn btn-primary saveWebhook"
                        data-botid="{{ $bot->id }}">Guardar</button>
                </form>
            </div>
        </div>
    </div>
</div>
