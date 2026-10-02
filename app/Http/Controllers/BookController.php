<?php

namespace App\Http\Controllers;

use App\Models\Book;
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
}
