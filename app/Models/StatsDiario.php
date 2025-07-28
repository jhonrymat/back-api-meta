<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatsDiario extends Model
{
    use HasFactory;

    protected $table = 'stats_diarios';

    protected $fillable = [
        'fecha',
        'phone_id',
        'status',
        'total',
        'distintivo',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'integer',
    ];
}
