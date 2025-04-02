<?php
namespace App\Jobs;
use App\Models\Newsletter;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Mail\NewsletterTestMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendBulkNewsletterJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $newsletter;
    public $emailTemplate;

    public function __construct(Newsletter $newsletter, EmailTemplate $emailTemplate)
    {
        $this->newsletter = $newsletter;
        $this->emailTemplate = $emailTemplate;
    }


    public function handle()
    {
        // Obtener los destinatarios dentro del job
        $recipients = $this->newsletter->getRecipients();

        // Verificar si hay destinatarios
        if ($recipients->isEmpty()) {
            Log::warning('No hay destinatarios para el boletín.');
            return;
        }

        // Divide los destinatarios en lotes para enviarlos en grupos
        $batchSize = 100; // Puedes ajustar este valor según el rendimiento de tu servidor
        $recipients->chunk($batchSize)->each(function ($recipientBatch) {
            // Intentar enviar un lote de correos
            try {
                dispatch(new SendNewsletterBatchJob($this->newsletter, $this->emailTemplate, $recipientBatch));
            } catch (\Exception $e) {
                // Si algo falla, registramos el error
                Log::error('Error al enviar lote de boletines: ' . $e->getMessage());
            }
        });
    }
}
