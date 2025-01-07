<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

require_once __DIR__ . '/Ubms_Mailable.php';

class BillNotification extends UBMS_Mailable
{
    use Queueable, SerializesModels;

    private $user;
    private $bills;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $bills)
    {
        $this->user = $user;
        $this->bills = $bills;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Unpaid Bill Notifications',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $locale = $this->user->language ?? 'en';

        return new Content(
            view: "emails/$locale/bill_notification",
            with: [
                'user' => $this->user,
                'bills' => $this->bills,
                'locale' => $locale,
                'website_url' => $this->website_url,
                'login_link' => $this->login_link,
                'company_name' => $this->get_company_name($locale),
                'customer_service_contact' => '',
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
