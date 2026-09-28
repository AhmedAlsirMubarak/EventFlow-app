<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Notifications/Index', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    /**
     * The latest few notifications for the top-bar bell dropdown. Fetched only
     * when the dropdown opens, so it adds nothing to ordinary page loads.
     */
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'notifications' => $user->notifications()->limit(8)->get()->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'body' => $notification->data['body'] ?? null,
                'read_at' => $notification->read_at,
                'created_at' => $notification->created_at,
            ]),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless(
            $notification->notifiable_type === $request->user()::class && $notification->notifiable_id === $request->user()->id,
            403
        );

        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('notifications.index'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }
}
