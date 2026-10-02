<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
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
        $book->load(['genres', 'reviews.user', 'reviews.likedByUsers']);

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
}
