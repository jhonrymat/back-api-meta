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

class SendNewsletterBatchJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public $newsletter;
    public $emailTemplate;
    public $recipients;

    public function __construct(Newsletter $newsletter, EmailTemplate $emailTemplate, $recipients)
    {
        $this->newsletter = $newsletter;
        $this->emailTemplate = $emailTemplate;
        $this->recipients = $recipients;
    }

    public function handle()
    {
        foreach ($this->recipients as $recipient) {
            if (empty($recipient->email)) {
                // Si no hay correo, no lo enviamos
                continue;
            }

            $content = str_replace(
                ['{{nombre}}', '{{email}}'],
                [$recipient->name ?? 'Usuario', $recipient->email],
                $this->newsletter->content
            );

            Mail::to($recipient->email)->send(
                new NewsletterTestMail($this->newsletter, $content, $this->emailTemplate)
            );
        }
    }
}
