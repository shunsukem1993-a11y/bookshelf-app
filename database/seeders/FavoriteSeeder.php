<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * 各ユーザーに3〜5冊のお気に入りを設定する（favoritesテーブル）。
     *
     * 件数はユーザーのインデックスから3〜5の範囲で決定し、
     * 書籍はユーザーのインデックスを開始位置としてローテーションで選出する。
     */
    public function run(): void
    {
        $users = User::orderBy('id')->get();
        $books = Book::orderBy('id')->get();
        $bookCount = $books->count();

        foreach ($users as $userIndex => $user) {
            $favoriteCount = 3 + ($userIndex % 3);
            $startOffset = ($userIndex * 2) % $bookCount;

            $favoriteBookIds = [];
            for ($i = 0; $i < $favoriteCount; $i++) {
                $favoriteBookIds[] = $books[($startOffset + $i) % $bookCount]->id;
            }

            $user->favoriteBooks()->syncWithoutDetaching($favoriteBookIds);
        }
    }
}
