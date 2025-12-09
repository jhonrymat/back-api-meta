<?php

namespace App\Models;

use Illuminate\Support\Facades\Bus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Envio extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombrePlantilla',
        'numeroDestinatarios',
        'body',
        'tag',
        'status',
    ];

    protected $casts = [
        'tag' => 'array',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_envios');
    }

    public function batch()
    {
        return $this->belongsTo(\Illuminate\Bus\Batch::class, 'batch_id');
    }

    // O mejor aún, usar el repositorio de batches
    public function getBatchStats()
    {
        if (!$this->batch_id) {
            return null;
        }

        return Bus::findBatch($this->batch_id);
    }
}
