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
            return $group->userEmails;
        });

        $tagRecipients = $this->tags->flatMap(fn ($tag) => $tag->contactos->map(function ($contacto) {
            return (object) [
                'name' => $contacto->nombre,
                'email' => $contacto->correo,
            ];
        }));

        return $groupRecipients
            ->merge($tagRecipients)
            ->filter(fn($recipient) => !empty($recipient->email))
            ->unique('email');
    }




}
