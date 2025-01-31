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
    public $content; // Contenido dinámico del correo
    public $emailTemplate;
    /**
     * Crear una nueva instancia del correo.
     *
     * @param Newsletter $newsletter
     * @param string $content
     * @param EmailTemplate $emailTemplate
     */
    public function __construct(Newsletter $newsletter, $content, $emailTemplate)
    {
        $this->newsletter = $newsletter;
        $this->content = $content;
        $this->emailTemplate = $emailTemplate;
    }
    /**
     * Construir el correo.
     *
     * @return $this
     */
    public function build()
    {
        $email = $this->subject($this->newsletter->subject)
            ->view('emails.newsletter') // Vista del correo
            ->with([
                'content' => $this->content,
                'emailTemplate' => $this->emailTemplate

        ]); // Contenido dinámico

        // Adjuntar el archivo PDF si existe
        if ($this->newsletter->has_attachment && $this->newsletter->attachment_path) {
            $email->attach(storage_path('app/public/' . $this->newsletter->attachment_path));
        }

        return $email;
    }
}
