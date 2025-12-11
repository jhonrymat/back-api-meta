<?php

namespace App\Jobs;

use App\Models\Newsletter;
use App\Models\EmailTemplate;
use App\Mail\NewsletterTestMail;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendNewsletterToUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $recipient;
    public $newsletter;
    public $emailTemplate;

    public $tries = 3;
    public $timeout = 60;
    public $backoff = [10, 30, 90];

    public function __construct($recipient, Newsletter $newsletter, EmailTemplate $emailTemplate)
    {
        $this->recipient = $recipient;
        $this->newsletter = $newsletter;
        $this->emailTemplate = $emailTemplate;
    }

    public function handle()
    {
        // Verificar email válido
        if (empty($this->recipient->email)) {
            Log::warning('Destinatario sin email', ['recipient_id' => $this->recipient->id ?? 'unknown']);
            return;
        }

        // Personalizar contenido
        $content = str_replace(
            ['{{nombre}}', '{{email}}'],
            [$this->recipient->name ?? 'Usuario', $this->recipient->email],
            $this->newsletter->content
        );

        try {
            Mail::to($this->recipient->email)->send(
                new NewsletterTestMail($this->newsletter, $content, $this->emailTemplate)
            );

            Log::info("✅ Email enviado", [
                'email' => $this->recipient->email,
                'newsletter_id' => $this->newsletter->id
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Error enviando email", [
                'email' => $this->recipient->email,
                'error' => $e->getMessage()
            ]);

            // Re-lanzar para reintentar
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('❌ Email job falló definitivamente', [
            'email' => $this->recipient->email ?? 'unknown',
            'newsletter_id' => $this->newsletter->id,
            'exception' => $exception->getMessage()
        ]);
    }
}
