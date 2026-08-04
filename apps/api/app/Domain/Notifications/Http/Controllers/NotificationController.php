<?php

namespace App\Domain\Notifications\Http\Controllers;

use App\Domain\Notifications\Http\Resources\AppNotificationResource;
use App\Domain\Notifications\Models\AppNotification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = AppNotification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(25);

        return AppNotificationResource::collection($notifications);
    }

    public function markRead(Request $request, AppNotification $notification)
    {
        abort_if($notification->user_id !== $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return AppNotificationResource::make($notification);
    }
}
