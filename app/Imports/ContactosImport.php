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


    public function __construct()
    {
        $this->user = Auth::user();
        // Crear un mapa de los nombres de los campos personalizados a sus IDs
        $this->customFieldMap = CustomField::where('user_id', $this->user->id)->pluck('id', 'name')->mapWithKeys(function ($item, $key) {
            return [$this->normalizeName($key) => $item];
        });
    }

    // Método para normalizar los nombres (reemplaza espacios por guiones bajos y pasa a minúsculas)
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
        $user = $this->user;

        // Obtener el número de fila actual
        static $rowIndex = 1;
        $currentRow = $rowIndex++;


        // Validar que las etiquetas existen antes de procesar el contacto
        if (!empty($row['tags'])) {
            $tagNames = explode(',', $row['tags']);
            $invalidTags = [];

            foreach ($tagNames as $tagName) {
                $tagNameTrimmed = trim($tagName);
                if (!Tag::where('nombre', $tagNameTrimmed)->exists()) {
                    $invalidTags[] = $tagNameTrimmed;
                }
            }

            // Si hay etiquetas inexistentes, lanzar error con la fila correspondiente
            if (!empty($invalidTags)) {
                $validator = Validator::make([], []);
                $validator->errors()->add("Fila {$currentRow}", "Las siguientes etiquetas no existen en la fila {$currentRow}: " . implode(', ', $invalidTags));

                throw ValidationException::withMessages($validator->errors()->toArray());
            }
        }

        // 📌 **Validar que el teléfono no esté duplicado**
        $contacto = Contacto::where('telefono', $row['telefono'])->first();

        if ($contacto) {
            if (!UserContact::where('user_id', $user->id)->where('contacto_id', $contacto->id)->exists()) {
                UserContact::create([
                    'user_id' => $user->id,
                    'contacto_id' => $contacto->id,
                ]);
            }

            // 🚫 Contacto ya existe, lo omitimos y registramos la fila
            $this->filasOmitidas[] = [
                'fila' => $currentRow,
                'telefono' => $row['telefono'],
                'motivo' => 'Ya existe en la base de datos',
            ];

            return null;
        } else {
            // ✅ Si el contacto no existe, lo creamos
            try {
                $contacto = Contacto::create([
                    'nombre' => $row['nombre'],
                    'apellido' => $row['apellido'],
                    'correo' => $row['correo'],
                    'telefono' => $row['telefono'],
                    'notas' => isset($row['notas']) ? $row['notas'] : null,
                ]);

                // Asociar el contacto al usuario autenticado
                UserContact::create([
                    'user_id' => $user->id,
                    'contacto_id' => $contacto->id,
                ]);
            } catch (\Exception $e) {
                // ⚠️ Si ocurre un error de duplicado de teléfono, lanzar un error de validación
                $validator = Validator::make([], []);
                $validator->errors()->add("Fila {$currentRow}", "Error: el teléfono {$row['telefono']} ya existe en la base de datos.");

                throw ValidationException::withMessages($validator->errors()->toArray());
            }
        }

        // ✅ Asociar etiquetas existentes al contacto
        if (!empty($row['tags'])) {
            $tagIds = Tag::whereIn('nombre', $tagNames)->pluck('id')->toArray();
            $contacto->tags()->syncWithoutDetaching($tagIds);
        }
    }




    public function batchSize(): int
    {
        return 4000;
    }

    public function chunkSize(): int
    {
        return 4000;
    }


    public function rules(): array
    {
        return [
            '*.nombre' => [
                'max:255',
                'required'
            ],
            '*.telefono' => [
                'required',
                'regex:/^57\d{10}$/'
            ],
            '*.tags' => [
                'required'
            ],
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.nombre.required' => 'El campo nombre es obligatorio.',
            '*.nombre.max' => 'El campo nombre no debe superar los 255 caracteres.',
            // '*.telefono.max' => 'El campo teléfono no debe superar los 12 dígitos.',
            '*.telefono.required' => 'El campo teléfono es obligatorio.',
            '*.tags.required' => 'El campo tags es obligatorio.',
        ];
    }

    protected function normalizePhone($telefono)
    {
        // Elimina todo lo que no sea número
        $telefono = preg_replace('/\D+/', '', $telefono);

        // Si no empieza con "57", lo agregamos
        if (!preg_match('/^57/', $telefono)) {
            $telefono = '57' . $telefono;
        }

        return $telefono;
    }

    public function prepareForValidation($data, $index)
    {
        if (!empty($data['telefono'])) {
            // Limpia y normaliza el número aquí
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
