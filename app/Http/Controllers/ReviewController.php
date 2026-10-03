<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Book;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    /**
     * 書籍にレビューを投稿する（認証必須・1人1冊1レビュー）。
     *
     * 投稿済みの場合は保存せず、詳細画面にエラーを表示する。
     * 同時送信でUNIQUE制約に引っかかった場合も同じエラーを返す。
     */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        if ($book->reviews()->where('user_id', $request->user()->id)->exists()) {
            return $this->redirectWithDuplicateError($book);
        }

        try {
            $book->reviews()->create([
                'user_id' => $request->user()->id,
                ...$request->validated(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return $this->redirectWithDuplicateError($book);
        }

        return redirect()->route('books.show', $book)->with('success', 'レビューを投稿しました。');
    }

    /**
     * 投稿済みエラーを付けて書籍詳細へリダイレクトする。
     */
    private function redirectWithDuplicateError(Book $book): RedirectResponse
    {
        return redirect()
            ->route('books.show', $book)
            ->with('error', 'この書籍には既にレビューを投稿済みです。');
    }
}
