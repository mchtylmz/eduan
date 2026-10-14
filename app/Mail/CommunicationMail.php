<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class CommunicationMail extends Mailable
{
    public function __construct(public string $messageTitle, public string $messageBody) {}

    public function build()
    {
        return $this->subject($this->messageTitle)->view('email.communication', ['messageTitle' => $this->messageTitle, 'messageBody' => $this->messageBody]);
    }
}
