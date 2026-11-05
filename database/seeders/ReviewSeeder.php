<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class ReviewSeeder extends Seeder
{
    /**
     * 評価（1〜5）ごとの日本語コメントテンプレート。
     *
     * ★応用：マイ読書レポートの評価分布グラフが意味のある分布になるよう、
     * 評価を1〜5の全範囲に拡大し、評価別のコメントを用意する。
     *
     * @var array<int, array<int, string>>
     */
    private array $commentsByRating = [
        5 => ['素晴らしい本でした！', '人生が変わりました。', '何度も読み返しています。'],
        4 => ['とても参考になりました。', '読みやすくておすすめです。', '期待通りの内容でした。'],
        3 => ['普通でした。', '可もなく不可もなく。', '期待したほどではなかった。'],
        2 => ['少し期待外れでした。', '内容が薄い印象。', 'もう少し深掘りしてほしかった。'],
        1 => ['残念ながら合いませんでした。', '期待と違いました。'],
    ];

    /**
     * レビューを投入する（各書籍2〜4件、ratingは1〜5）。
     *
     * ★応用：レビュー件数・評価・投稿者をランダム化する。
     * 1冊の書籍に同一ユーザーから複数レビューが付かないよう、
     * random()で重複なしにユーザーを抽出する。
     */
    public function run(): void
    {
        $users = User::all();

        Book::all()->each(function (Book $book) use ($users) {
            $reviewCount = rand(2, 4);
            $reviewers = $users->random($reviewCount);

            $reviewers->each(function (User $reviewer) use ($book) {
                $rating = rand(1, 5);

                Review::create([
                    'user_id' => $reviewer->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => Arr::random($this->commentsByRating[$rating]),
                ]);
            });
        });
    }
}
