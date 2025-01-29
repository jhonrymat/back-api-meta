<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Contacto;

class ContactoComponent extends Component
{
    public $tags; // Variable pública para almacenar los tags
    public $customFields;
    public $selectedId = null; // ID del contacto seleccionado
    public $nombre, $apellido, $correo, $telefono, $notas, $tagsSeleccionados = [];
    public $showEditModal = false; // Control del modal de edición
    public $showDeleteModal = false; // Control del modal de eliminación
    public $message = '¡Hola desde Livewire!';

    protected $listeners = ['edit', 'delete'];
    protected $rules = [
        'nombre' => 'required|string|max:255',
        'apellido' => 'nullable|string|max:255',
        'correo' => 'nullable|email|max:255',
        'telefono' => 'required|string|max:255',
        'notas' => 'nullable|string',
        'tagsSeleccionados' => 'array',
        'tagsSeleccionados.*' => 'exists:tags,id',
    ];
    public function mount()
    {
        // Verifica si el usuario no está autenticado
        if (!auth()->check()) {
            return redirect('/login'); // Redirige al usuario no autenticado
        }

        $user = auth()->user(); // Obtén el usuario autenticado
        $this->tags = $user->tags()->get(); // Obtén los tags relacionados
        $this->customFields = $user->customFields()->get(); // Obtén los campos personalizados relacionados
    }
    public function create()
    {
    }
    public function edit($Id)
    {
        $contacto = Contacto::findOrFail($Id);
        $this->selectedId = $contacto->id;
        $this->nombre = $contacto->nombre;
        $this->apellido = $contacto->apellido;
        $this->correo = $contacto->correo;
        $this->telefono = $contacto->telefono;
        $this->notas = $contacto->notas;
        $this->tagsSeleccionados = $contacto->tags->pluck('id')->toArray();
        $this->showEditModal = true;
    }


    public function updateMessage()
    {
        $this->message = 'Mensaje actualizado en tiempo real.';
    }
    public function update()
    {
        $this->validate();
        $contacto = Contacto::findOrFail($this->selectedId);
        $contacto->update([
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'correo' => $this->correo,
            'telefono' => $this->telefono,
            'notas' => $this->notas,
        ]);
        $contacto->tags()->sync($this->tagsSeleccionados);
        $this->resetModal();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Contacto actualizado correctamente']);
    }
    public function showDelete($Id)
    {
        $this->selectedId = $Id;
        $this->showDeleteModal = true;
    }
    public function delete()
    {
        $contacto = Contacto::findOrFail($this->selectedId);
        $contacto->tags()->detach();
        $contacto->delete();
        $this->resetModal();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Contacto eliminado correctamente']);
    }
    private function resetModal()
    {
        $this->reset(['selectedId', 'nombre', 'apellido', 'correo', 'telefono', 'notas', 'tagsSeleccionados', 'showEditModal', 'showDeleteModal']);
    }
    public function render()
    {
        
        return view('livewire.contacto-component', [
            'tags' => $this->tags, // Opcional: pasar tags a la vista
            'customFields' => $this->customFields, // Opcional: pasar campos personalizados a la vista
        ]);
    }
}
