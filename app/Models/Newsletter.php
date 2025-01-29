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
        return $this->belongsToMany(Group::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tag_newsletter');
    }
}
