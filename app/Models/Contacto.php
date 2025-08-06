<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'telefono',
        'notas',
        'tiene_mensajes_nuevos',
    ];



    // public function mensajes()
    // {
    //     return $this->hasMany(Message::class, 'wa_id', 'telefono');
    // }
    public function messages()
    {
        return $this->hasMany(Message::class, 'wa_id', 'telefono');
    }

    public function createWithTags(array $data, $clientId)
    {
        $contacto = $this->create($data);

        // Obtén los tags a partir de los datos y el cliente específico
        $tagNames = explode(',', $data['tags']);
        $tags = Tag::where('client_id', $clientId)->whereIn('nombre', $tagNames)->pluck('id');

        // Relaciona los tags al contacto
        $contacto->tags()->sync($tags);

        return $contacto;
    }

    public function createWithDefaultTag(array $data, $defaultTagName = 'Pendiente')
    {
        $userId = auth()->id();

        $contacto = $this->create($data);

        // Busca o crea el tag solo para el usuario actual
        $tag = Tag::firstOrCreate(
            ['nombre' => $defaultTagName, 'user_id' => $userId],
            ['descripcion' => 'Descripción pendiente', 'color' => 'gray']
        );

        // Asocia el tag con el contacto, registrando el user_id en la tabla pivote
        $contacto->tags()->attach($tag->id, ['user_id' => $userId]);

        return $contacto;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_contacts', 'contacto_id', 'user_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'contacto_tag', 'contacto_id', 'tag_id')
            ->withPivot('user_id');
    }


    public function customFieldValues()
    {
        return $this->hasMany(CustomFieldValue::class, 'contacto_id');
    }
}
