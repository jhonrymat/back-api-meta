<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

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
