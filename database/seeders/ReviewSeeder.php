<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * レビューを32件投入する（各書籍2〜4件、ratingは3〜5）。
     *
     * 書籍ごとに3件（最後の1冊のみ2件）とし、レビュアーは書籍のインデックスを
     * 開始位置として5人のユーザーをローテーションで選出する。
     */
    public function run(): void
    {
        $users = User::orderBy('id')->get();
        $books = Book::orderBy('id')->get();
        $userCount = $users->count();

        $commentsByRating = [
            5 => 'とても良い本でした。読んでよかったです。',
            4 => '読みやすく、参考になる内容でした。',
            3 => '普通の内容でした。',
        ];
        $ratings = [5, 4, 3];

        foreach ($books as $bookIndex => $book) {
            $isLastBook = $bookIndex === ($books->count() - 1);
            $reviewCount = $isLastBook ? 2 : 3;

            for ($i = 0; $i < $reviewCount; $i++) {
                $reviewer = $users[($bookIndex + $i) % $userCount];
                $rating = $ratings[$i];

                Review::create([
                    'user_id' => $reviewer->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => $commentsByRating[$rating],
                ]);
            }
        }
    }
}
