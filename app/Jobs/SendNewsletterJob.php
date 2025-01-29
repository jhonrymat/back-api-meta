<?php
namespace App\Jobs;
use App\Models\Newsletter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewsletterTestMail;
class SendNewsletterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $newsletter;
    /**
     * Crear una nueva instancia del job.
     *
     * @param Newsletter $newsletter
     */
    public function __construct(Newsletter $newsletter)
    {
        $this->newsletter = $newsletter;
    }
    /**
     * Ejecutar el trabajo.
     *
     * @return void
     */
    public function handle()
    {
        $recipients = $this->newsletter->groups->flatMap->userEmails; // Obtener destinatarios
        foreach ($recipients as $recipient) {
            // Reemplazar variables dinámicas
            $personalizedContent = str_replace(
                ['{{Nombre}}', '{{Email}}'], // Variables dinámicas
                [$recipient->name, $recipient->email], // Datos reales del destinatario
                $this->newsletter->content
            );
            // Enviar correo con contenido personalizado
            Mail::to($recipient->email)->send(new NewsletterTestMail($this->newsletter, $personalizedContent));
        }
    }
}
