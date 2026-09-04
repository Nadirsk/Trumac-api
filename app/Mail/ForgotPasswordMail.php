<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public object $user;

    public function __construct(object $user)
    {
        $this->user = $user;
    }

    public function build(): static
    {
        return $this->from('support@techieshark.com', 'TotoRide')
            ->subject('Forgot Password - ' . $this->user->name)
            ->view('mails.forgot_password_mail');
    }
}
