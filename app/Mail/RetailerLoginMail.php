<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RetailerLoginMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $retailerName;
    public string $shopName;
    public string $phone;
    public string $email;
    public string $password;

    public function __construct(
        string $retailerName,
        string $shopName,
        string $phone,
        string $email,
        string $password
    ) {
        $this->retailerName = $retailerName;
        $this->shopName     = $shopName;
        $this->phone        = $phone;
        $this->email        = $email;
        $this->password     = $password;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Trumac — Your Login Credentials',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.retailer-login',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
