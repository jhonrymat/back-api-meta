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
use Log;

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
    $recipients = $this->newsletter->getRecipients();

    if ($recipients->isEmpty()) {
        Log::warning('No hay destinatarios para el boletín.');
        return;
    }

    foreach ($recipients as $recipient) {
        if (empty($recipient->email)) {
            continue;
        }

        // Despachar un job por destinatario
        dispatch(new SendNewsletterToUserJob($recipient, $this->newsletter, $this->emailTemplate))
            ->onQueue('email-queue');
    }
}

}
