<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BroadcastCertificate extends Mailable
{
    use Queueable, SerializesModels;

    protected $email_info;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email_info)
    {
        $this->email_info=$email_info;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.Broadcast.BroadcastCertificate', $this->email_info)
            ->attachFromStorage('certificate/1.pdf')
            ->subject('Assessment Certificate');
    }
}
