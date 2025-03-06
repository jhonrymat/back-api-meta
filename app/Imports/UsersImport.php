<?php
namespace App\Imports;

use App\Models\UserEmail;
use App\Events\ImportFailed;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class UsersImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, ShouldQueue
{
    private $group;

    public function __construct($group)
    {
        $this->group = $group;
    }


    public function model(array $row)
    {
        try {
            // Validar que el correo no sea nulo
            if (!isset($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                Log::warning('🚨 Correo ignorado en importación: ' . ($row['email'] ?? 'N/A'));
                return null; // Ignorar filas sin email válido
            }

            // 🔹 Insertar siempre un nuevo usuario (permitiendo duplicados)
            $user = UserEmail::create([
                'name' => $row['name'] ?? null,
                'email' => trim($row['email']),
            ]);

            // Relacionar con el grupo
            $this->group->userEmails()->attach($user->id);
        } catch (\Exception $e) {
            // 🔹 Loguear el error y disparar el evento de error
            Log::error('❌ Error en la importación: ' . $e->getMessage());
            event(new ImportFailed('La importación falló: ' . $e->getMessage()));
        }

        return null; // Evitar que Laravel Excel intente crear duplicados automáticamente
    }


    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
