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

    // ✅ SOLUCIÓN 1: Guardar solo los datos necesarios (sin modelo completo)
    public string $recipientEmail;
    public string $recipientName;
    public int $recipientId;

    public Newsletter $newsletter;
    public EmailTemplate $emailTemplate;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [10, 30, 90];

    /**
     * ✅ Constructor corregido: extraer solo datos necesarios
     */
    public function __construct($recipient, Newsletter $newsletter, EmailTemplate $emailTemplate)
    {
        // ✅ Extraer datos del recipient en vez de guardar el objeto completo
        $this->recipientEmail = $recipient->email ?? '';
        $this->recipientName = $recipient->name ?? 'Usuario';
        $this->recipientId = $recipient->id ?? 0;

        $this->newsletter = $newsletter;
        $this->emailTemplate = $emailTemplate;
    }

    public function handle()
    {
        // ✅ Validación temprana
        if (empty($this->recipientEmail)) {
            Log::warning('Job sin email válido', [
                'recipient_id' => $this->recipientId,
                'newsletter_id' => $this->newsletter->id
            ]);
            return; // No falla, simplemente se salta
        }

        // ✅ Verificar que el batch no ha sido cancelado
        if ($this->batch() && $this->batch()->cancelled()) {
            Log::info('Batch cancelado, saltando email', [
                'email' => $this->recipientEmail,
                'batch_id' => $this->batch()->id
            ]);
            return;
        }

        try {
            // Personalizar contenido
            $content = str_replace(
                ['{{nombre}}', '{{email}}'],
                [$this->recipientName, $this->recipientEmail],
                $this->newsletter->content
            );

            // Enviar email
            Mail::to($this->recipientEmail)->send(
                new NewsletterTestMail($this->newsletter, $content, $this->emailTemplate)
            );

            Log::info("✅ Email enviado correctamente", [
                'email' => $this->recipientEmail,
                'newsletter_id' => $this->newsletter->id,
                'batch_id' => $this->batch()?->id
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Error enviando email", [
                'email' => $this->recipientEmail,
                'newsletter_id' => $this->newsletter->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // ✅ Re-lanzar solo si no hemos agotado los intentos
            if ($this->attempts() < $this->tries) {
                throw $e; // Reintentará
            }

            // Si ya agotamos intentos, loguear y no relanzar
            Log::error('❌ Email agotó todos los intentos', [
                'email' => $this->recipientEmail,
                'attempts' => $this->attempts()
            ]);
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('❌ Email job falló definitivamente', [
            'email' => $this->recipientEmail,
            'recipient_id' => $this->recipientId,
            'newsletter_id' => $this->newsletter->id,
            'exception' => $exception->getMessage(),
            'batch_id' => $this->batch()?->id
        ]);
    }

    /**
     * ✅ OPCIONAL: Especificar tags para Horizon
     */
    public function tags(): array
    {
        return [
            'newsletter:' . $this->newsletter->id,
            'email:' . substr($this->recipientEmail, 0, 20),
        ];
    }
}
