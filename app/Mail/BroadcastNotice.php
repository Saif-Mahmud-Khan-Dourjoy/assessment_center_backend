<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BroadcastNotice extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $title='Default title of the message!';
    protected $body='This is default message of body';
    protected $name = 'First-name Last-name';
    public function __construct($title, $body, $firs_name, $last_name)
    {
        $this->title =$title;
        $this->body = $body;
        $this->name = $firs_name." ".$last_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.Broadcast.BroadcastNotice', [
            'name'=>$this->name,
            'title'=>$this->title,
            'body'=>$this->body
        ]);
    }
}
