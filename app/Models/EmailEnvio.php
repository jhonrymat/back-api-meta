<?php

namespace App\Models;

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
}
