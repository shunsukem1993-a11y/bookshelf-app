<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * 各レビューに0〜3人のいいねを設定する（review_userテーブル、自分のレビューを除く）。
     *
     * いいね数はレビューのインデックスから0〜3の範囲で決定し、
     * 投稿者の次のユーザーから順に（投稿者自身を除外して）選出する。
     */
    public function run(): void
    {
        $users = User::orderBy('id')->get();
        $userCount = $users->count();
        $reviews = Review::orderBy('id')->get();

        foreach ($reviews as $reviewIndex => $review) {
            $likeCount = $reviewIndex % 4;
            $authorIndex = $users->search(
                fn (User $user): bool => $user->id === $review->user_id
            );

            $likerIds = [];
            $offset = 1;
            while (count($likerIds) < $likeCount) {
                $candidate = $users[($authorIndex + $offset) % $userCount];
                if ($candidate->id !== $review->user_id) {
                    $likerIds[] = $candidate->id;
                }
                $offset++;
            }

            $review->likedByUsers()->syncWithoutDetaching($likerIds);
        }
    }
}
