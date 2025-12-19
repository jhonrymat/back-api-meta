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

    public string $recipientEmail;
    public string $recipientName;
    public int $recipientId;
    public Newsletter $newsletter;
    public EmailTemplate $emailTemplate;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [10, 30, 90];

    // ✅ NUEVO: Configuración SQS optimizada
    public int $maxExceptions = 3; // Para allowFailures

    public function __construct($recipient, Newsletter $newsletter, EmailTemplate $emailTemplate)
    {
        $this->recipientEmail = $recipient->email ?? '';
        $this->recipientName = $recipient->name ?? 'Usuario';
        $this->recipientId = $recipient->id ?? 0;
        $this->newsletter = $newsletter;
        $this->emailTemplate = $emailTemplate;
    }

    public function handle()
    {
        // ✅ Validaciones tempranas
        if (empty($this->recipientEmail)) {
            Log::warning('Job sin email válido', [
                'recipient_id' => $this->recipientId,
                'newsletter_id' => $this->newsletter->id
            ]);
            return;
        }

        // ✅ Verificar batch cancelado (sin acceder a propiedades que causan DB query)
        if ($this->batch() && $this->batch()->cancelled()) {
            Log::info('Batch cancelado, saltando email', [
                'email' => $this->recipientEmail
            ]);
            return;
        }

        try {
            $content = str_replace(
                ['{{nombre}}', '{{email}}'],
                [$this->recipientName, $this->recipientEmail],
                $this->newsletter->content
            );

            Mail::to($this->recipientEmail)->send(
                new NewsletterTestMail($this->newsletter, $content, $this->emailTemplate)
            );

            Log::info("✅ Email enviado", [
                'email' => $this->recipientEmail,
                'newsletter_id' => $this->newsletter->id
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Error enviando email", [
                'email' => $this->recipientEmail,
                'newsletter_id' => $this->newsletter->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 90);
                return;
            }

            Log::error('❌ Email agotó intentos', [
                'email' => $this->recipientEmail
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('❌ Email job falló definitivamente', [
            'email' => $this->recipientEmail,
            'recipient_id' => $this->recipientId,
            'newsletter_id' => $this->newsletter->id,
            'exception' => $exception->getMessage()
        ]);
    }

    public function tags(): array
    {
        return [
            'newsletter:' . $this->newsletter->id,
            'email:' . substr($this->recipientEmail, 0, 20),
        ];
    }
}
