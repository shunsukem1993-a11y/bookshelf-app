<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧を表示する（ゲストも閲覧可能）。
     */
    public function index(): View
    {
        $books = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * 書籍詳細を表示する（ゲストも閲覧可能）。
     */
    public function show(Book $book): View
    {
        // レビューは投稿日時の新しい順に表示する
        $book->load([
            'genres',
            'reviews' => fn ($query) => $query->latest(),
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍登録フォームを表示する（認証必須）。
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を登録する（認証必須）。
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $book = Book::create([
            'user_id' => $request->user()->id,
            ...$request->safe()->except('genres'),
        ]);

        $book->genres()->sync($request->validated('genres'));

        return redirect()->route('books.index')->with('success', '書籍を登録しました。');
    }

    /**
     * 書籍編集フォームを表示する（作成者本人のみ）。
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍を更新する（作成者本人のみ）。
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $book->update($request->safe()->except('genres'));
        $book->genres()->sync($request->validated('genres'));

        return redirect()->route('books.show', $book)->with('success', '書籍を更新しました。');
    }

    /**
     * 書籍を削除する（作成者本人のみ）。
     *
     * 関連データ（book_genre・reviews・favorites・いいね）はDBの外部キー制約
     * （cascadeOnDelete）により自動的に削除される。
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }
}
