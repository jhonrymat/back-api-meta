<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{

    protected $table = 'messages';

    /**
     * 🔑 CRÍTICO: Campos permitidos para mass assignment
     * Esto soluciona el error: "Add [wam_id] to fillable property"
     */
    protected $fillable = [
        'wam_id',          // ⚡ CRÍTICO para updateOrCreate
        'body',
        'outgoing',
        'type',
        'wa_id',
        'phone_id',
        'status',
        'caption',
        'data',
        'distintivo',
        'code',
        'created_at',      // Para timestamps personalizados
        'updated_at',
    ];

    /**
     * Cast de atributos
     */
    protected $casts = [
        'outgoing' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // public function contacto()
    // {
    //     return $this->belongsTo(Contacto::class, 'wa_id', 'telefono');
    // }

    public function contacto()
    {
        return $this->belongsTo(Contacto::class, 'wa_id', 'telefono');
    }

    public function numeroRelacion()
    {
        return $this->belongsTo(\App\Models\Numeros::class, 'phone_id', 'id_telefono');
    }
}
