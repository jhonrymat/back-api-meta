<?php

namespace App\Imports;

use App\Models\Tag;
use App\Models\Contacto;
use App\Models\CustomField;
use App\Models\UserContact;
use App\Models\CustomFieldValue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ContactosImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, WithCustomCsvSettings, SkipsEmptyRows
{
    protected $user;
    protected $customFieldMap;
    protected $filasOmitidas = [];

    // Cache para evitar múltiples consultas
    protected $existingPhones = [];
    protected $allTags = [];

    public function __construct()
    {
        $this->user = Auth::user();

        // Cachear campos personalizados
        $this->customFieldMap = CustomField::where('user_id', $this->user->id)
            ->pluck('id', 'name')
            ->mapWithKeys(function ($id, $name) {
                return [$this->normalizeName($name) => $id];
            });

        // Cachear todas las etiquetas existentes
        $this->allTags = Tag::pluck('id', 'nombre')->mapWithKeys(function ($id, $name) {
            return [strtolower(trim($name)) => $id];
        });

        // Cachear los teléfonos de contactos ya asociados al usuario
        $this->existingPhones = Contacto::join('user_contacts', 'contactos.id', '=', 'user_contacts.contacto_id')
            ->where('user_contacts.user_id', $this->user->id)
            ->pluck('telefono')
            ->toArray();
    }

    protected function normalizeName($name)
    {
        return str_replace(' ', '_', strtolower($name));
    }

    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'UTF-8'
        ];
    }

    public function model(array $row)
    {
        static $rowIndex = 1;
        $currentRow = $rowIndex++;

        $telefono = $row['telefono'];

        // Validar si ya existe el contacto para el usuario actual
        if (in_array($telefono, $this->existingPhones)) {
            $this->filasOmitidas[] = [
                'fila' => $currentRow,
                'telefono' => $telefono,
                'motivo' => 'Ya existe en la base de datos',
            ];
            return null;
        }

        // Validar etiquetas
        $tagIds = [];
        if (!empty($row['tags'])) {
            $tagNames = explode(',', $row['tags']);
            $invalidTags = [];

            foreach ($tagNames as $name) {
                $key = strtolower(trim($name));
                if (isset($this->allTags[$key])) {
                    $tagIds[] = $this->allTags[$key];
                } else {
                    $invalidTags[] = $name;
                }
            }

            if (!empty($invalidTags)) {
                throw ValidationException::withMessages([
                    "Fila {$currentRow}" => "Las siguientes etiquetas no existen: " . implode(', ', $invalidTags),
                ]);
            }
        }

        try {
            DB::beginTransaction();

            $contacto = Contacto::create([
                'nombre' => $row['nombre'],
                'apellido' => $row['apellido'],
                'correo' => $row['correo'],
                'telefono' => $telefono,
                'notas' => $row['notas'] ?? null,
            ]);

            UserContact::create([
                'user_id' => $this->user->id,
                'contacto_id' => $contacto->id,
            ]);

            if (!empty($tagIds)) {
                $contacto->tags()->syncWithoutDetaching($tagIds);
            }

            // Agregar teléfono a cache para evitar duplicados en siguientes filas
            $this->existingPhones[] = $telefono;

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            $validator = Validator::make([], []);
            $validator->errors()->add("Fila {$currentRow}", "Error al guardar el contacto {$telefono}: {$e->getMessage()}");
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return null;
    }

    public function batchSize(): int
    {
        return 200;
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function rules(): array
    {
        return [
            '*.nombre' => ['required', 'max:255'],
            '*.telefono' => ['required', 'regex:/^57\d{10}$/'],
            '*.tags' => ['required'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.nombre.required' => 'El campo nombre es obligatorio.',
            '*.nombre.max' => 'El campo nombre no debe superar los 255 caracteres.',
            '*.telefono.required' => 'El campo teléfono es obligatorio.',
            '*.tags.required' => 'El campo tags es obligatorio.',
        ];
    }

    public function prepareForValidation($data, $index)
    {
        if (!empty($data['telefono'])) {
            $telefono = preg_replace('/\D+/', '', $data['telefono']);
            if (!preg_match('/^57/', $telefono)) {
                $telefono = '57' . $telefono;
            }
            $data['telefono'] = $telefono;
        }

        return $data;
    }

    public function getFilasOmitidas(): array
    {
        return $this->filasOmitidas;
    }
}
