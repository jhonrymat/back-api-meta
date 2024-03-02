<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TareaProgramada extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_app',
        'phone_id',
        'numeros',
        '$payload',
        'body',
        'messageData',
        'status',
        'fecha_programada'
    ];
}
