<?php

namespace App\Mail;
require_once __DIR__ . '/Ubms_Mailable.php';

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationStatusNotification extends UBMS_Mailable
{
    use Queueable, SerializesModels;

    private $user_locale;
    private $owner_name;
    private $household_name;
    private $status;

    public function __construct($user_locale, $owner_name, $household_name, $status)
    {
        parent::__construct();
        $this->user_locale = $user_locale;
        $this->owner_name = $owner_name;
        $this->household_name = $household_name;
        $this->status = $status;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Invitation Request Status',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: "emails/$this->user_locale/invitation_status",
            with: [
                'ownerName' => $this->owner_name,
                'householdName' => $this->household_name,
                'status' => $this->status,
                'billingSystemName' => $this->get_company_name($this->user_locale),
                'supportEmail' => $this->support_email,
                'websiteURL' => $this->website_url,
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
