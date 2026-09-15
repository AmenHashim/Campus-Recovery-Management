<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = AppNotification::where('user_id', Auth::id())->latest()->get();

        return view('student.notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $notifications->filter->isUnread()->count(),
        ]);
    }

    public function markRead(AppNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === Auth::id(), 403);

        $notification->update(['read_at' => now()]);

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        $count = AppNotification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('status', $count === 1 ? '1 notification marked as read.' : "{$count} notifications marked as read.");
    }
}
