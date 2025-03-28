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

class SendBulkNewsletterJob implements ShouldQueue
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
            dispatch(new SendNewsletterJob($this->newsletter, $this->emailTemplate, $recipient));
        }
    }
}
