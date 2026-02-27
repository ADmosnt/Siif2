<?php
//app/Mail/ContactMail.php
//Esta clase se encarga de construir el correo que se enviará cuando un usuario complete el formulario de contacto

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

public function envelope(): Envelope
{
    return new Envelope(
        // Usa la dirección configurada en .env (MAIL_FROM_ADDRESS)
        from: new Address(config('mail.from.address'), config('mail.from.name')), 
        replyTo: [
            new Address($this->data['email'], $this->data['nombre']),
        ],
        subject: 'Contacto SIIF: ' . $this->data['asunto'],
    );
}

    public function content(): Content
    {
        return new Content(
            view: 'contact',
            with: [
                'data' => $this->data,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}