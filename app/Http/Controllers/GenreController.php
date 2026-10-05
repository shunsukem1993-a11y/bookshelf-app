<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Database\QueryException;
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

    /**
     * ジャンルを削除する（認証必須）。書籍が紐づいている場合は削除せず、一覧にエラーを表示する。
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        $linkedMessage = 'このジャンルには書籍が紐づいているため削除できません。';

        // 分かりやすいメッセージを出すため、先に紐づきを確認する
        if ($genre->books()->exists()) {
            return redirect()->route('genres.index')->with('error', $linkedMessage);
        }

        try {
            $genre->delete();
        } catch (QueryException $e) {
            // 1451: 外部キー制約違反（確認と削除の間に書籍が紐付けられた場合）
            if (($e->errorInfo[1] ?? null) !== 1451) {
                throw $e;
            }

            return redirect()->route('genres.index')->with('error', $linkedMessage);
        }

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました。');
    }
}
