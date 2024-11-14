<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BillNotification extends Mailable
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

        $website_url = config('app.url');
        $login_link = $website_url . '/login';
        $locale = $this->user->language ?? 'en';

        $app_name = config('app.name');
        $app_name_hr = env('ubms_app_name_hr');

        return new Content(
            view: "emails/$locale/bill_notification",
            with: [
                'user' => $this->user,
                'bills' => $this->bills,
                'locale' => $locale,
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
