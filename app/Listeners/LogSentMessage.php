<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogSentMessage
{
    public function handle(MessageSent $event)
    {
        $messageId = $event->data['__laravel_notification_id'] ?? Str::uuid();
        Storage::disk('emails')->put(
            sprintf('%s_%s.eml', now()->format('YmdHis'), $messageId),
            $event->message->toString()
        );
    }
}
