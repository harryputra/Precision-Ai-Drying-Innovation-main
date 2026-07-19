<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationWebController extends Controller
{
    public function index(Request $request): View
    {
        $query = Notification::forUser(auth()->id())->latest();

        if ($request->boolean('unread')) $query->unread();
        if ($request->query('type') === 'alerts') $query->alerts();

        $notifications = $query->paginate(20)->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Notification $notification): RedirectResponse
    {
        $notification->markAsRead();
        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        Notification::forUser(auth()->id())->unread()->update(['read_at' => now()]);
        return back()->with('success', 'All notifications marked as read.');
    }
}
