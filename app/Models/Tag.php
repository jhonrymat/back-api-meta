<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    protected $fillable = ['nombre', 'descripcion', 'color', 'user_id'];  // Asegúrate de incluir todos los campos que deseas asignar masivamente

    public function contactos()
    {
        return $this->belongsToMany(Contacto::class, 'contacto_tag', 'tag_id', 'contacto_id');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function newsletters()
    {
        return $this->belongsToMany(Newsletter::class, 'tag_newsletter');
    }
}
