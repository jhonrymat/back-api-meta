<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Newsletter extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'subject',
        'copy_email',
        'has_attachment',
        'attachment_path',
        'content',
        'is_cancelled',
        'is_sent', // 👈 Agregado
    ];

    protected $casts = [
        'is_cancelled' => 'boolean',
        'is_sent' => 'boolean', // 👈 Agregado
    ];

    public function getAttachmentUrlAttribute()
    {
        return $this->attachment_path ? asset('storage/' . $this->attachment_path) : null;
    }
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_newsletter');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tag_newsletter');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRecipients()
    {
        $groupRecipients = $this->groups->flatMap(function ($group) {
            return $group->userEmails->map(function ($userEmail) use ($group) {
                $userEmail->origen_tipo = 'group';
                $userEmail->origen_id = $group->id;
                return $userEmail;
            });
        });

        $tagRecipients = $this->tags->flatMap(function ($tag) {
            return $tag->contactos->map(function ($contacto) use ($tag) {
                return (object) [
                    'name' => $contacto->nombre,
                    'email' => $contacto->correo,
                    'origen_tipo' => 'tag',
                    'origen_id' => $tag->id,
                ];
            });
        });

        // Combinamos TODO (sin deduplicar aún) para guardar el origen completo
        $todosConOrigen = $groupRecipients->merge($tagRecipients)
            ->filter(fn($recipient) => !empty($recipient->email));

        // Deduplicado para el envío real (1 correo por persona)
        $recipientsUnicos = $todosConOrigen->unique('email');

        // Guardamos la relación completa para trazabilidad (no deduplicada)
        return [
            'unicos' => $recipientsUnicos,
            'origenes' => $todosConOrigen,
        ];
    }


}
