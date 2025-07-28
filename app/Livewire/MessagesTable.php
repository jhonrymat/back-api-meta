<?php

namespace App\Livewire;

use App\Models\Message;
use App\Models\Numeros;
use Illuminate\Support\Facades\Auth;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class MessagesTable extends DataTableComponent
{
    protected $model = Message::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setPaginationEnabled()
            ->setPerPageAccepted([50, 100, 150]) // Opciones que el usuario puede elegir
            ->setPerPage(50) // Valor por defecto (50 por página)
            ->setDelaySelectAllEnabled()   // Retrasa la selección global para mejorar rendimiento
            ->setPaginationMethod('cursor') // Usa cursor pagination para grandes datasets :contentReference[oaicite:2]{index=2}
            ->setSearchThrottle(300);      // Aplica debounce a la búsqueda (300 ms) :contentReference[oaicite:3]{index=3}
    }

    public function mount()
    {
        if (!auth()->check()) {
            redirect()->route('home')->send();
        }
    }

    public function builder(): \Illuminate\Database\Eloquent\Builder
    {
        $user = auth()->user();

        // Obtener los id_telefono de los números asociados al usuario usando la relación
        $phoneIds = $user->numeros()->pluck('id_telefono');

        // Si no tiene números, retornar una query vacía para evitar errores
        if ($phoneIds->isEmpty()) {
            return Message::query()->whereRaw('1 = 0');
        }

        return Message::query()
            ->select([
                'messages.id',
                'messages.wa_id',
                'messages.phone_id',
                'messages.body',
                'messages.status',
                'messages.code',
                'messages.created_at',
                'messages.updated_at',
            ])
            ->where('type', 'template')
            ->whereIn('phone_id', $phoneIds)
            ->orderBy('created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id")->sortable()->searchable()->collapseOnMobile(),
            Column::make("Celular", "wa_id")->sortable()->searchable(),
            Column::make('Número', 'numeroRelacion.numero')
                ->sortable()
                ->searchable()
                ->collapseOnMobile(),
            Column::make("Mensaje", "body")->sortable()->searchable()->collapseOnMobile(),
            Column::make("Estado", "status")
                ->sortable()
                ->searchable()
                ->format(function ($value, $row) {
                    switch ($value) {
                        case 'sent':
                            return '<span style="background-color: #FFC107; color: white; padding: 4px 8px; text-align: center; border-radius: 5px;">Enviado</span>';
                        case 'read':
                            return '<span style="background-color: #28A745; color: white; padding: 4px 8px; text-align: center; border-radius: 5px;">Leído</span>';
                        case 'failed':
                            return '<span style="background-color: #DC3545; color: white; padding: 4px 8px; text-align: center; border-radius: 5px;">Fallido</span>';
                        case 'delivered':
                            return '<span style="background-color: #17A2B8; color: white; padding: 4px 8px; text-align: center; border-radius: 5px;">Entregado</span>';
                        case 'received':
                            return '<span style="background-color: #17A2B8; color: white; padding: 4px 8px; text-align: center; border-radius: 5px;">Recibido</span>';
                        default:
                            return '<span style="background-color: #6c757d; color: white; padding: 4px 8px; text-align: center; border-radius: 5px;">' . $value . '</span>';
                    }
                })
                ->html(), // Activa la renderización del HTML,
            Column::make("Error", "code")->sortable()->searchable()->collapseOnMobile(),
            Column::make("Creado", "created_at")->sortable()->searchable()->collapseOnMobile(),
            Column::make("Actualizado", "updated_at")->sortable()->searchable()->collapseOnMobile(),
        ];
    }
}
