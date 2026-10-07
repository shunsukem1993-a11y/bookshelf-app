<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * 書籍にレビューを投稿する（認証必須。1冊に複数回投稿できる）。
     */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        $book->reviews()->create([
            'user_id' => $request->user()->id,
            ...$request->validated(),
        ]);

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
}
