<?php
namespace App\Jobs;

use App\Models\Newsletter;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\NewsletterTestMail;

class SendNewsletterToUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $recipient;
    public $newsletter;
    public $emailTemplate;

    public function __construct($recipient, Newsletter $newsletter, EmailTemplate $emailTemplate)
    {
        $this->recipient = $recipient;
        $this->newsletter = $newsletter;
        $this->emailTemplate = $emailTemplate;
    }

    public function handle()
    {
        // Recargar el estado actualizado del boletín
        $this->newsletter->refresh();

        if ($this->newsletter->is_cancelled) {
            Log::info("Boletín cancelado. No se enviará a: {$this->recipient->email}");
            return;
        }
        if (empty($this->recipient->email)) {
            return;
        }

        $content = str_replace(
            ['{{nombre}}', '{{email}}'],
            [$this->recipient->name ?? 'Usuario', $this->recipient->email],
            $this->newsletter->content
        );

        try {
            // Log::info('Enviando boletín a ' . $this->recipient->email);
            Mail::to($this->recipient->email)->send(
                new NewsletterTestMail($this->newsletter, $content, $this->emailTemplate)
            );
        } catch (\Exception $e) {
            Log::error("Error al enviar a {$this->recipient->email}: " . $e->getMessage());
        }
    }
}

