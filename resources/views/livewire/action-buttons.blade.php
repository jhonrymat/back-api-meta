<div class="btn-group">
    <button class="btn btn-info btn-sm" wire:click="$dispatch('edit', { Id: '{{ $row->id }}' })">
        Editar
    </button>
    <button class="btn btn-danger btn-sm" wire:click="$dispatch('delete', { Id: '{{ $row->id }}' })">
        Eliminar
    </button>
</div>
