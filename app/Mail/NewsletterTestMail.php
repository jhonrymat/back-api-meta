<?php
namespace App\Mail;

use App\Models\Newsletter;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $newsletter;
    public $content;
    public $emailTemplate;

    public function __construct(Newsletter $newsletter, $content, ?EmailTemplate $emailTemplate)
    {
        $this->newsletter = $newsletter;
        $this->content = $content;
        $this->emailTemplate = $emailTemplate ?? $this->defaultTemplate(); // Si no hay, usa una plantilla por defecto
    }

    public function build()
    {
        // Datos que se enviarán a la vista del correo
        $viewData = [
            'newsletter' => $this->newsletter,
            'content' => $this->content,
            'emailTemplate' => $this->emailTemplate,
        ];

        $email = $this->subject($this->newsletter->subject)
            ->view('emails.newsletter')
            ->with($viewData);

        // Adjuntar archivo PDF si existe
        if ($this->newsletter->has_attachment && $this->newsletter->attachment_path) {
            $email->attach(storage_path('app/public/' . $this->newsletter->attachment_path));
        }

        return $email;
    }

    /**
     * Retorna una plantilla predeterminada si el usuario no seleccionó ninguna.
     */
    private function defaultTemplate()
    {
        return (object) [
            'card_background_color' => '#ffffff',
            'header_color' => '#12b5ec',
            'footer_color' => '#f1f1f1',
            'logo' => null,
            'title' => 'Boletín Informativo',
            'footer_text' => 'Este es un correo de prueba. Todos los derechos reservados.',
        ];
    }
}
