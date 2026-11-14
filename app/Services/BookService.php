<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BookService
{
    /**
     * 書籍を新規登録し、ジャンルを紐づける。
     *
     * 書籍の登録とジャンルの同期を1つのトランザクションにまとめることで、
     * 一方のみが反映される不整合を防ぐ。
     *
     * @param  array<string, mixed>  $validated  バリデーション済みの書籍属性（genresを含む）
     */
    public function createWithGenres(User $user, array $validated): Book
    {
        return DB::transaction(function () use ($user, $validated) {
            $book = Book::create([
                'user_id' => $user->id,
                ...Arr::except($validated, 'genres'),
            ]);

            $book->genres()->sync($validated['genres']);

            return $book;
        });
    }

    /**
     * 書籍を更新し、ジャンルを同期する。
     *
     * 書籍の更新とジャンルの同期を1つのトランザクションにまとめることで、
     * 一方のみが反映される不整合を防ぐ。
     *
     * @param  array<string, mixed>  $validated  バリデーション済みの書籍属性（genresを含む）
     */
    public function updateWithGenres(Book $book, array $validated): Book
    {
        DB::transaction(function () use ($book, $validated) {
            $book->update(Arr::except($validated, 'genres'));
            $book->genres()->sync($validated['genres']);
        });

        return $book;
    }
}
