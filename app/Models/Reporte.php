<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    use HasFactory;
    protected $fillable = ['fechaInicio', 'fechaFin', 'id_telefono', 'archivo'];

    public function archivoExiste()
    {
        return file_exists(storage_path('app/exports/' . $this->archivo));
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_reportes', 'reporte_id', 'user_id');
    }
}
