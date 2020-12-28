<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCredentials extends Mailable
{
    use Queueable, SerializesModels;

        private $username = '';
        private $userpass = '';
        private $useremail = '';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($username, $userpass)
    {
        $this->username = $username;
        $this->userpass= $userpass;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->out = new \Symfony\Component\Console\Output\ConsoleOutput(); 
        $this->out->writeln([
            'username'=> $this->username,
            'password'=> $this->userpass,
            'url'=>env('FRONT_END_HOME'),
        ]);
        return $this->markdown('emails.userCredentials',[
            'username'=> $this->username,
            'password'=> $this->userpass,
            'url'=>env('FRONT_END_HOME'),
        ]);
    }
}
