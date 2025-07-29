<?php

namespace App\Livewire;

use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class MessagesTable extends DataTableComponent
{
    protected $model = Message::class;

    public array $bulkActions = [];

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setPaginationEnabled()
            ->setPaginationMethod('cursor') // ✅ Ideal para BigData
            ->setPerPageAccepted([50, 100])
            ->setPerPage(50)
            ->setSearchThrottle(500) // Reduce carga al buscar
            ->setAdditionalSelects(['messages.id']) // ✅ Necesario
            ->setQueryStringStatus(false);
    }

    public function builder(): \Illuminate\Database\Eloquent\Builder
    {
        $user = Auth::user();
        $phoneIds = $user->numeros()->pluck('id_telefono');

        if ($phoneIds->isEmpty()) {
            return Message::query()->whereRaw('1 = 0'); // No resultados
        }

        return Message::query()
            ->select([
                'messages.id',
                'messages.wa_id',
                'messages.status',
                'messages.code',
                'messages.created_at'
            ])
            ->where('type', 'template') // ✅ Está indexado
            ->whereIn('phone_id', $phoneIds) // ✅ Está indexado
            ->orderByDesc('created_at'); // ✅ Parte del índice
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id")->sortable()->collapseOnMobile(),
            Column::make("Celular", "wa_id")
                ->sortable()
                ->searchable(fn($builder, $term) => $builder->where('wa_id', 'LIKE', "$term%"))
                ->collapseOnMobile(),
            Column::make("Estado", "status")
                ->sortable()
                ->format(fn($value) => $this->badgeStatus($value))
                ->html(),
            Column::make("Error", "code")->collapseOnMobile(),
            Column::make("Creado", "created_at")->sortable()->collapseOnMobile(),
        ];
    }

    private function badgeStatus($status)
    {
        return match ($status) {
            'sent' => $this->badge('Enviado', '#FFC107'),
            'read' => $this->badge('Leído', '#28A745'),
            'failed' => $this->badge('Fallido', '#DC3545'),
            'delivered' => $this->badge('Entregado', '#17A2B8'),
            'received' => $this->badge('Recibido', '#17A2B8'),
            default => $this->badge($status, '#6c757d'),
        };
    }

    private function badge($text, $color)
    {
        return "<span style=\"background-color: $color; color: white; padding: 4px 8px; border-radius: 5px;\">$text</span>";
    }
}
