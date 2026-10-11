<?php

namespace Webkul\Notification\Services;

use Illuminate\Support\Collection;
use Webkul\Notification\Models\UserNotification;
use Webkul\Notification\Repositories\UserNotificationRepository;

class NotificationService
{
    public function __construct(
        protected UserNotificationRepository $notifications,
    ) {}

    public function notify(
        int $userId,
        string $type,
        string $title,
        ?string $body = null,
        ?string $link = null,
        array $meta = [],
        ?string $entityType = null,
        ?int $entityId = null,
    ): UserNotification {
        return $this->notifications->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'meta' => $meta ?: null,
        ]);
    }

    public function unreadCount(int $userId): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return Collection<int, UserNotification>
     */
    public function recent(int $userId, int $limit = 15): Collection
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function markRead(int $userId, int $id): void
    {
        $row = UserNotification::query()
            ->where('user_id', $userId)
            ->whereKey($id)
            ->first();

        $row?->markRead();
    }

    public function markAllRead(int $userId): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
