<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
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

    /**
     * ジャンル登録フォームを表示する（認証必須）。
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンルを登録する（認証必須）。成功時はジャンル一覧へリダイレクトする。
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        Genre::create($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを登録しました。');
    }

    /**
     * ジャンル編集フォームを表示する（認証必須）。
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンルを更新する（認証必須）。成功時はジャンル一覧へリダイレクトする。
     */
    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを更新しました。');
    }
}
