<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\Models\AppNotification;
use App\Mail\GenericNotificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Every notification goes through this single entry point and fans out to
 * both channels confirmed in scope: in-app (AppNotification row) + email
 * (via the log mail driver locally — no real SMTP needed to verify it
 * fires). SMS/push are explicitly out of scope per the spec.
 */
class NotificationService
{
    public function notify(User $user, string $type, string $title, string $body, ?int $relatedEmploymentId = null): AppNotification
    {
        $notification = AppNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'related_employment_id' => $relatedEmploymentId,
        ]);

        // ->send() (not ->queue()) — no queue worker runs in this local/demo
        // setup, and the log mail driver makes synchronous sends cheap.
        Mail::to($user->email)->send(new GenericNotificationMail($title, $body));

        return $notification;
    }
}
