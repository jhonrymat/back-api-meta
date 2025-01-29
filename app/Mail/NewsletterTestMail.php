<?php
namespace App\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Newsletter;
class NewsletterTestMail extends Mailable
{
    use Queueable, SerializesModels;
    public $newsletter;
    public $content; // Contenido dinámico del correo
    /**
     * Crear una nueva instancia del correo.
     *
     * @param Newsletter $newsletter
     * @param string $content
     */
    public function __construct(Newsletter $newsletter, $content)
    {
        $this->newsletter = $newsletter;
        $this->content = $content;
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
            ->with(['content' => $this->content]); // Contenido dinámico

        // Adjuntar el archivo PDF si existe
        if ($this->newsletter->has_attachment && $this->newsletter->attachment_path) {
            $email->attach(storage_path('app/public/' . $this->newsletter->attachment_path));
        }

        return $email;
    }
}
