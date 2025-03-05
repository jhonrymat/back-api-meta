<?php
namespace App\Imports;

use App\Models\UserEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow, ShouldQueue
{
    private $group;

    public function __construct($group)
    {
        $this->group = $group;
    }

    public function model(array $row)
    {
        // Validar que el correo no sea nulo
        if (!isset($row['email']) || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            return null; // Ignorar filas sin email válido
        }

        // 🔹 Evitar duplicados antes de insertar
        $existingUser = UserEmail::where('email', $row['email'])->first();

        if (!$existingUser) {
            $user = UserEmail::create([
                'name' => $row['name'] ?? null,
                'email' => trim($row['email']),
            ]);

            // Relacionar con el grupo
            $this->group->userEmails()->attach($user->id);
        } else {
            // Si ya existe, solo lo asociamos al grupo sin insertarlo
            $this->group->userEmails()->syncWithoutDetaching([$existingUser->id]);
        }

        return null; // Evitar que Laravel Excel intente crear duplicados
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
