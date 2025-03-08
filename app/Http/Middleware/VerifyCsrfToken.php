<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/whatsapp-webhook',
        '/store-data',
        '/refresh-csrf',
        '/check-session',
        '/ask-bot-embedded',
        '/upload-image',
        '/admin/ask-bot-embedded',
        '/admin/upload-image',
        '/livewire/*',
        '/contact',
        '/livewire/update', // Excluir actualización de Livewire
        '/public/store-contact',
    ];
}
