<?php

namespace App\Livewire;

use App\Models\Media;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class MediaPicker extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $typeFilter = '';
    public $selectedMedia = [];
    public $multiple = false;
    public $uploadedFiles = [];
    public $showModal = false;

    protected $paginationTheme = 'bootstrap';

    public function mount($multiple = false, $selected = [])
    {
        $this->multiple = $multiple;
        $this->selectedMedia = is_array($selected) ? $selected : [$selected];
    }

    public function updatedUploadedFiles()
    {
        $this->validate([
            'uploadedFiles.*' => 'file|max:51200',
        ]);

        foreach ($this->uploadedFiles as $file) {
            $controller = new \App\Http\Controllers\MediaController();
            $media = $controller->storeMedia($file);

            if (!$this->multiple) {
                $this->selectedMedia = [$media->id];
            } else {
                $this->selectedMedia[] = $media->id;
            }
        }

        $this->uploadedFiles = [];
        $this->dispatch('mediaUploaded');
    }

    public function selectMedia($mediaId)
    {
        if (!$this->multiple) {
            $this->selectedMedia = [$mediaId];
        } else {
            if (in_array($mediaId, $this->selectedMedia)) {
                $this->selectedMedia = array_diff($this->selectedMedia, [$mediaId]);
            } else {
                $this->selectedMedia[] = $mediaId;
            }
        }
    }

    public function confirmSelection()
    {
        $this->dispatch('mediaSelected', $this->selectedMedia);
        $this->showModal = false;
    }

    public function render()
    {
        $query = Media::query()
            ->where('user_id', auth()->id()) // ← AGREGAR ESTA LÍNEA
            ->recent();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('file_name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->typeFilter) {
            switch ($this->typeFilter) {
                case 'image':
                    $query->where('mime_type', 'like', 'image/%');
                    break;
                case 'video':
                    $query->where('mime_type', 'like', 'video/%');
                    break;
                case 'document':
                    $query->whereIn('extension', ['pdf', 'doc', 'docx']);
                    break;
            }
        }

        $media = $query->paginate(12);

        return view('livewire.media-picker', compact('media'));
    }
}
