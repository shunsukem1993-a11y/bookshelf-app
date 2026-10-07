<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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
     * レビュー編集フォームを表示する（投稿者本人のみ）。
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューを更新する（投稿者本人のみ。認可はUpdateReviewRequestで行う）。
     */
    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $review->update($request->validated());

        return redirect()
            ->route('books.show', $review->book_id)
            ->with('success', 'レビューを更新しました。');
    }

    /**
     * レビューを削除する（投稿者本人のみ）。
     *
     * 関連するいいね（review_likes）はDBの外部キー制約（cascadeOnDelete）により自動的に削除される。
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $book = $review->book;
        $review->delete();

        return redirect()
            ->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
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
