<div class="container">
    @if (isset($errors) && $errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <ul>
                    <li>{{ $error }}</li>
                </ul>
            @endforeach
        </div>
    @endif
    <div class="card shadow-lg">
        {{-- boton Maddigo volver a /home --}}
        <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <!-- Botón Volver a Inicio -->
            <a href="{{ route('home') }}" class="btn btn-info btn-sm" style="color: white;">
                <i class="fas fa-home"></i> Volver a Inicio
            </a>

            <!-- Título centrado -->
            <h3 class="mb-0 text-center flex-grow-1" style="margin-right: 7.5rem;">Contactos</h3>
        </div>
        <div class="card-body">
            <!-- Más contenido aquí -->
            <div>
                @livewire('contactos-datatable')
            </div>
        </div>
    </div>
    <!-- Modal para Editar -->
    @if ($showEditModal)
        <div class="modal fade show" style="display: block;" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Contacto</h5>
                        <button class="btn-close" wire:click="$set('showEditModal', false)" wire:loading.attr="disabled"
                            wire:loading.class="disabled" wire:target="update"></button>

                    </div>
                    <div class="modal-body">
                        <input type="text" wire:model.defer="nombre" class="form-control mb-2" placeholder="Nombre">
                        <input type="text" wire:model.defer="apellido" class="form-control mb-2"
                            placeholder="Apellido">
                        <input type="email" wire:model.defer="correo" class="form-control mb-2" placeholder="Correo">
                        <input type="text" wire:model.defer="telefono" class="form-control mb-2"
                            placeholder="Teléfono">
                        <textarea wire:model.defer="notas" class="form-control mb-2" placeholder="Notas"></textarea>
                        {{-- etiqueta --}}
                        <select wire:model.defer="tagsSeleccionados" class="form-control mb-2" multiple>
                            @foreach ($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="$set('showEditModal', false)"
                            wire:loading.attr="disabled" wire:loading.class="disabled" wire:target="update">
                            Cancelar
                        </button>

                        <button class="btn btn-primary" wire:click="update" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="update">Guardar</span>
                            <span wire:loading wire:target="update">
                                <i class="fas fa-spinner fa-spin"></i> Guardando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="modal fade show" style="display: block;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Eliminar Contacto</h5>
                        <button class="btn-close" wire:click="$set('showDeleteModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de eliminar este contacto?</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="$set('showDeleteModal', false)">Cancelar</button>
                        <button class="btn btn-danger" wire:click="delete">Eliminar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <x-sweet-alert-good></x-sweet-alert-good>
</div>
