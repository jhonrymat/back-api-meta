<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'name',
        'file_name',
        'mime_type',
        'disk',
        'path',
        'size',
        'extension',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size' => 'integer',
    ];

    protected $appends = ['url', 'human_readable_size', 'type'];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getTypeAttribute(): string
    {
        $mime = $this->mime_type;

        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (str_starts_with($mime, 'audio/')) return 'audio';
        if (in_array($mime, ['application/pdf'])) return 'pdf';
        if (in_array($this->extension, ['doc', 'docx'])) return 'document';
        if (in_array($this->extension, ['xls', 'xlsx'])) return 'spreadsheet';
        if (in_array($this->extension, ['zip', 'rar', '7z'])) return 'archive';

        return 'file';
    }

    public function getIconClassAttribute(): string
    {
        return match($this->type) {
            'image' => 'fas fa-image text-primary',
            'video' => 'fas fa-video text-danger',
            'audio' => 'fas fa-music text-info',
            'pdf' => 'fas fa-file-pdf text-danger',
            'document' => 'fas fa-file-word text-primary',
            'spreadsheet' => 'fas fa-file-excel text-success',
            'archive' => 'fas fa-file-archive text-warning',
            default => 'fas fa-file text-secondary',
        };
    }

    // Métodos útiles
    public function delete()
    {
        // Eliminar archivo físico
        if (Storage::disk($this->disk)->exists($this->path)) {
            Storage::disk($this->disk)->delete($this->path);
        }

        return parent::delete();
    }

    public function download()
    {
        return Storage::disk($this->disk)->download($this->path, $this->file_name);
    }

    // Scopes
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeVideos($query)
    {
        return $query->where('mime_type', 'like', 'video/%');
    }

    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
