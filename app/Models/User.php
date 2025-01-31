<?php

namespace App\Models;

use App\Models\Aplicaciones;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relación muchos a muchos con Aplicaciones
    public function aplicaciones()
    {
        return $this->belongsToMany(Aplicaciones::class, 'user_aplicaciones', 'user_id', 'aplicacion_id');
    }

    public function numeros()
    {
        return $this->belongsToMany(Numeros::class, 'user_numeros', 'user_id', 'numero_id');
    }

    public function tags()
    {
        return $this->hasMany(Tag::class, 'user_id');
    }

    public function getTagsCountAttribute()
    {
        return $this->tags()->count();
    }

    public function contactos()
    {
        return $this->belongsToMany(Contacto::class, 'user_contacts', 'user_id', 'contacto_id');
    }

    public function getContactosCountAttribute()
    {
        return $this->contactos()->count();
    }

    public function envios()
    {
        return $this->belongsToMany(Envio::class, 'user_envios');
    }

    public function tareasProgramadas()
    {
        return $this->belongsToMany(TareaProgramada::class, 'user_tarea_programada', 'user_id', 'tarea_programada_id');
    }

    public function customFields()
    {
        return $this->hasMany(CustomField::class, 'user_id');
    }

    public function reportes()
    {
        return $this->belongsToMany(Reporte::class, 'user_reportes', 'user_id', 'reporte_id');
    }

    public function bots()
    {
        return $this->hasMany(Bot::class);
    }


    public function groups()
    {
        return $this->hasMany(Group::class);
    }


}
