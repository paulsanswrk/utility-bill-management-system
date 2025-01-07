<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChangeEmailNotification extends Mailable
{
    use Queueable, SerializesModels;

    private $user;
    private $uuid;

    /**
     * Create a new message instance.
     */
    public function __construct($user, $uuid)
    {
        $this->user = $user;
        $this->uuid = $uuid;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $locale = $this->user->language ?? 'en';
        return new Envelope(
            subject: __("messages.request_email_address_change", [], $locale),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {

        $website_url = rtrim(config('app.url'), '/');
        $login_link = $website_url . '/login';
        $locale = $this->user->language ?? 'en';

        $app_name = config('app.name');
        $app_name_hr = env('ubms_app_name_hr');

        $confirmation_link = "confirmemailchange/{$this->uuid}";

        return new Content(
            view: "emails/$locale/change_email_notification",
            with: [
                'user' => $this->user,
                'confirmation_link' => "$website_url/$confirmation_link",
                'website_url' => $website_url,
                'login_link' => $login_link,
                'company_name' => $locale == 'en'? $app_name : $app_name_hr,
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
