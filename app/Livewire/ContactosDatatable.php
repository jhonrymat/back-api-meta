<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Contacto;

class ContactosDatatable extends DataTableComponent
{
    protected $model = Contacto::class;
    protected $listeners = ['Updated' => '$refresh']; // Refrescar la tabla

    public ?int $searchFilterDebounce = 600;
    public array $perPageAccepted = [10, 20, 50, 100];
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
        $this->setSingleSortingStatus(false);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->searchable()
                ->sortable(),
            Column::make("Nombre", "nombre")
                ->searchable()
                ->sortable(),
            Column::make("Apellido", "apellido")
                ->collapseAlways()
                ->sortable(),
            Column::make("Etiquetas")
                ->label(function ($row) {
                    // Recuperar etiquetas asociadas al contacto
                    return $row->tags->pluck('nombre')->implode(', ');
                })
                ->collapseAlways()
                ->sortable(),
            Column::make("Correo", "correo")
                ->collapseOnMobile()
                ->searchable()
                ->sortable(),
            Column::make("Telefono", "telefono")
                ->collapseOnMobile()
                ->searchable()
                ->sortable(),
            Column::make("Notas", "notas")
                ->collapseAlways()
                ->sortable(),
            Column::make("Created at", "created_at")
                ->sortable(),
            Column::make("Updated at", "updated_at")
                ->collapseAlways(),
            Column::make("Acciones")
                ->label(
                    fn($row) => view('livewire.action-buttons', ['row' => $row])
                ),
        ];
    }
}
