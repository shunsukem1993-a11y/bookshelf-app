<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル一覧を表示する（認証必須。各ジャンルの書籍数を付け、登録順に並べる）。
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')
            ->orderBy('id')
            ->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル詳細を表示する（認証必須。紐づく書籍を10件/ページ、登録順に並べる）。
     */
    public function show(Genre $genre): View
    {
        // 中間テーブルにもidがあるため、テーブル名を付けて曖昧さを避ける
        $books = $genre->books()
            ->with('genres')
            ->orderBy('books.id')
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }
}
