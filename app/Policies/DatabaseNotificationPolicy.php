<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationPolicy
{
    /**
     * ユーザーが通知を既読にできるかを判定する（本人の通知のみ）。
     */
    public function markAsRead(User $user, DatabaseNotification $notification): bool
    {
        return $notification->notifiable_type === $user->getMorphClass()
            && $notification->notifiable_id === $user->getKey();
    }
}
