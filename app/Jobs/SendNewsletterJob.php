<?php
namespace App\Jobs;
use App\Models\Newsletter;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use App\Mail\NewsletterTestMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendNewsletterJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $newsletter;
    public $emailTemplate;
    public $recipient;

    public function __construct(Newsletter $newsletter, EmailTemplate $emailTemplate, $recipient)
    {
        $this->newsletter = $newsletter;
        $this->emailTemplate = $emailTemplate;
        $this->recipient = $recipient;
    }

    public function handle()
    {
        if (empty($this->recipient->email)) {
            // \Log::warning("Correo no enviado: destinatario sin email. ID: {$this->recipient->id}");
            return; // Sale sin procesar el envío
        }

        $content = str_replace(
            ['{{nombre}}', '{{email}}'],
            [$this->recipient->name ?? 'Usuario', $this->recipient->email],
            $this->newsletter->content
        );

        Mail::to($this->recipient->email)->send(
            new NewsletterTestMail($this->newsletter, $content, $this->emailTemplate)
        );
        // Registrar en logs que el correo fue enviado con éxito
        // \Log::info("Correo enviado exitosamente a: {$this->recipient->email}");
    }
}
