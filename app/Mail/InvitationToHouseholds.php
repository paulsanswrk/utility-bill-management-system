<?php

namespace App\Mail;

require_once __DIR__ . '/Ubms_Mailable.php';

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationToHouseholds extends UBMS_Mailable
{
    use Queueable, SerializesModels;

    private $user_locale;
    private $invitation_uuid;
    private $inviter_name;
    private $invitee_name;
    private $hh_names;

    public function __construct($user_locale, $invitation_uuid, $inviter_name, $invitee_name, $hh_names)
    {
        parent::__construct();
        $this->user_locale = $user_locale;
        $this->invitation_uuid = $invitation_uuid;
        $this->inviter_name = $inviter_name;
        $this->invitee_name = $invitee_name;
        $this->hh_names = $hh_names;
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation To Households',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: "emails/$this->user_locale/invitation_to_household",
            with: [
                'inviteeName' => $this->invitee_name,
                'inviterName' => $this->inviter_name,
                'households' => $this->hh_names,

                'billingSystemName' => $this->get_company_name($this->user_locale),
                'acceptLink' => "{$this->website_url}/households/accept/{$this->invitation_uuid}",
                'declineLink' => "{$this->website_url}/households/decline/{$this->invitation_uuid}",
                'websiteURL' => $this->website_url,
                'supportEmail' => $this->support_email,

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
