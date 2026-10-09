<?php

namespace Webkul\Activity\Http\Controllers;

use Illuminate\Routing\Controller;

class ReminderNotificationController extends Controller
{
    public function index()
    {
        $notifications = $this->query()
            ->orderByRaw('read_at is null desc')
            ->latest()
            ->paginate(20);

        return response()->json([
            ...$notifications->toArray(),
            'unread' => $this->query()->whereNull('read_at')->count(),
        ]);
    }

    public function read(string $id)
    {
        $this->query()->findOrFail($id)->markAsRead();

        return response()->noContent();
    }

    private function query()
    {
        return auth()->guard('user')->user()->notifications();
    }
}
