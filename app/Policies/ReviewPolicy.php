<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * ユーザーがレビューを更新できるかを判定する（投稿者本人のみ）。
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * ユーザーがレビューを削除できるかを判定する（投稿者本人のみ）。
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }
}
