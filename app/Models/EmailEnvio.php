<?php

namespace App\Models;

use Illuminate\Support\Facades\Bus;
use Illuminate\Database\Eloquent\Model;

class EmailEnvio extends Model
{
    protected $fillable = [
        'newsletter_id',
        'user_id',
        'batch_id',
        'numero_destinatarios',
        'status',
        'scheduled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function newsletter()
    {
        return $this->belongsTo(Newsletter::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener estadísticas del batch
     */
    public function getBatchStats()
    {
        if (!$this->batch_id) {
            return null;
        }

        try {
            return Bus::findBatch($this->batch_id);
        } catch (\Exception $e) {
            return null;
        }
    }
}
