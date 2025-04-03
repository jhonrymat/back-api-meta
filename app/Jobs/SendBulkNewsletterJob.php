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
        $recipients = $this->newsletter->getRecipients();

        if ($recipients->isEmpty()) {
            Log::warning('No hay destinatarios para el boletín.');
            return;
        }

        Log::info('Enviando boletín a ' . $recipients->count() . ' destinatarios.');

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
