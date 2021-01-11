<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BroadcastResult extends Mailable
{
    use Queueable, SerializesModels;

    private $student_info=[];
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email_info)
    {
        $this->student_info=$email_info;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.Broadcast.BroadcastResult', $this->student_info)
                    ->subject('Assessment Result');
    }
}
