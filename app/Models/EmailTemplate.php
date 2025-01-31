<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailTemplate extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'html_content',
        'logo',
        'title',
        'card_background_color',
        'header_color',
        'footer_color',
        'footer_text'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
