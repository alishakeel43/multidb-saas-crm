<?php

namespace Webkul\Notification\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Notification\Models\UserNotification;
use Webkul\Notification\Services\NotificationService;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notifications,
    ) {}

    public function index(): View
    {
        $userId = (int) auth()->guard('user')->id();

        return view('notification::index', [
            'notifications' => UserNotification::query()
                ->where('user_id', $userId)
                ->latest('id')
                ->paginate(30),
        ]);
    }

    public function feed(): JsonResponse
    {
        $userId = (int) auth()->guard('user')->id();

        return response()->json([
            'unread' => $this->notifications->unreadCount($userId),
            'items' => $this->notifications->recent($userId)->map(fn (UserNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'link' => $n->link,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->diffForHumans(),
            ])->values(),
        ]);
    }

    public function markRead(int $id): JsonResponse
    {
        $this->notifications->markRead((int) auth()->guard('user')->id(), $id);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(): RedirectResponse|JsonResponse
    {
        $count = $this->notifications->markAllRead((int) auth()->guard('user')->id());

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'count' => $count]);
        }

        session()->flash('success', trans('notification::app.messages.marked-all-read'));

        return redirect()->route('admin.notifications.index');
    }
}
