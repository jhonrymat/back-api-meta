<div>
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" wire:model.live="search" class="form-control" placeholder="Buscar medios...">
                </div>
                <div class="col-md-4">
                    <select wire:model.live="typeFilter" class="form-control">
                        <option value="">Todos los tipos</option>
                        <option value="image">Imágenes</option>
                        <option value="video">Videos</option>
                        <option value="audio">Audio</option>
                        <option value="document">Documentos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fileUpload{{ $this->id }}" class="btn btn-primary btn-block mb-0">
                        <i class="fas fa-upload"></i> Subir
                    </label>
                    <input type="file" id="fileUpload{{ $this->id }}" wire:model="uploadedFiles" multiple style="display:none;">
                </div>
            </div>
        </div>
        <div class="card-body" style="max-height: 500px; overflow-y: auto;">
            @if($uploadedFiles)
                <div class="alert alert-info">
                    <i class="fas fa-spinner fa-spin"></i> Subiendo archivos...
                </div>
            @endif

            <div class="row">
                @forelse($media as $item)
                    <div class="col-lg-2 col-md-3 col-4 mb-3">
                        <div class="card h-100 {{ in_array($item->id, $selectedMedia) ? 'border-primary' : '' }}"
                             wire:click="selectMedia({{ $item->id }})"
                             style="cursor: pointer;">
                            <div class="card-body p-2 text-center">
                                @if($item->type == 'image')
                                    <img src="{{ $item->url }}" alt="{{ $item->name }}" class="img-fluid" style="max-height: 80px;">
                                @else
                                    <i class="{{ $item->icon_class }} fa-2x"></i>
                                @endif

                                @if(in_array($item->id, $selectedMedia))
                                    <div class="position-absolute" style="top: 5px; right: 5px;">
                                        <i class="fas fa-check-circle text-primary fa-lg"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="card-footer p-1">
                                <small class="d-block text-truncate" title="{{ $item->name }}">
                                    {{ $item->name }}
                                </small>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-4">
                        <i class="fas fa-folder-open fa-3x text-muted"></i>
                        <p class="text-muted mt-2">No hay medios disponibles</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-3">
                {{ $media->links() }}
            </div>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <small class="text-muted">
                        {{ count($selectedMedia) }} medio(s) seleccionado(s)
                    </small>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="confirmSelection"
                            {{ count($selectedMedia) == 0 ? 'disabled' : '' }}>
                        <i class="fas fa-check"></i> Seleccionar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
