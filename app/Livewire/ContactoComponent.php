<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Contacto;
use Illuminate\Support\Facades\DB;

class ContactoComponent extends Component
{
    public $tags; // Variable pública para almacenar los tags
    public $customFields;
    public $selectedId = null; // ID del contacto seleccionado
    public $nombre, $apellido, $correo, $telefono, $notas, $tagsSeleccionados = [];
    public $showEditModal = false; // Control del modal de edición
    public $showDeleteModal = false; // Control del modal de eliminación

    protected $listeners = ['edit', 'showDelete'];
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
            return response()->redirectTo(route('home'));
        }

        $user = auth()->user(); // Obtén el usuario autenticado
        $this->tags = $user->tags()->get(); // Obtén los tags relacionados
        $this->customFields = $user->customFields()->get(); // Obtén los campos personalizados relacionados
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
        // Obtener la primera etiqueta del contacto (ajústalo si es necesario)
        $this->tagsSeleccionados = $contacto->tags()
            ->wherePivot('user_id', auth()->id())
            ->pluck('tags.id')
            ->toArray();

        $this->showEditModal = true;
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
        $userId = auth()->id();

        // Obtener tags actuales del usuario
        $currentTags = $contacto->tags()
            ->wherePivot('user_id', $userId)
            ->pluck('tags.id')
            ->toArray();

        // Determinar qué agregar y qué quitar
        $tagsToAdd = array_diff($this->tagsSeleccionados, $currentTags);
        $tagsToRemove = array_diff($currentTags, $this->tagsSeleccionados);

        // Agregar nuevas relaciones
        foreach ($tagsToAdd as $tagId) {
            $contacto->tags()->syncWithoutDetaching([
                $tagId => ['user_id' => $userId]
            ]);
        }

        // Eliminar relaciones antiguas del usuario
        if (!empty($tagsToRemove)) {
            DB::table('contacto_tag')
                ->where('contacto_id', $contacto->id)
                ->whereIn('tag_id', $tagsToRemove)
                ->where('user_id', $userId)
                ->delete();
        }

        $this->resetModal();
        $this->dispatch('Updated');
        $this->dispatch('sweet-alert-good', icon: 'success', title: 'Exito.!', text: 'Contacto actualizado correctamente.');
    }
    public function showDelete($Id)
    {
        $this->selectedId = $Id;
        $this->showDeleteModal = true;
    }
    public function delete()
    {
        $contacto = Contacto::findOrFail($this->selectedId);
        // 🚫 Verificar si tiene mensajes asociados
        if ($contacto->messages()->exists()) {
            $this->dispatch('sweet-alert-good', icon: 'error', title: 'Error', text: 'No se puede eliminar un contacto con mensajes asociados.');
            return;
        }
        $contacto->tags()->wherePivot('user_id', auth()->id())->detach();
        $contacto->delete();

        $this->resetModal();
        $this->dispatch('Updated');
        $this->dispatch('sweet-alert-good', icon: 'success', title: 'Exito.!', text: 'Contacto eliminado correctamente.');
    }
    private function resetModal()
    {
        $this->reset(['selectedId', 'nombre', 'apellido', 'correo', 'telefono', 'notas', 'tagsSeleccionados', 'showEditModal', 'showDeleteModal']);
        $this->resetValidation();
    }
    public function render()
    {

        return view('livewire.contacto-component', [
            'tags' => $this->tags, // Opcional: pasar tags a la vista
            'customFields' => $this->customFields, // Opcional: pasar campos personalizados a la vista
        ]);
    }
}
