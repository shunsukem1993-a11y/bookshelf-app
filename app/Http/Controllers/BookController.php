<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧を表示する（ゲストも閲覧可能）。
     *
     * ★応用：キーワード（title・author部分一致）・ジャンルでの絞り込みと、
     * 並び順（newest/oldest/title/rating）に対応する。ページネーション時は
     * 検索条件を維持する。
     */
    public function index(Request $request): View
    {
        $sort = $request->input('sort', 'newest');

        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->string('keyword');

                $query->where(
                    fn ($q) => $q->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%")
                );
            })
            ->when($request->filled('genre'), function ($query) use ($request) {
                $genreId = $request->integer('genre');

                $query->whereHas('genres', fn ($q) => $q->where('genres.id', $genreId));
            })
            ->when($sort === 'oldest', fn ($query) => $query->oldest())
            ->when($sort === 'title', fn ($query) => $query->orderBy('title'))
            ->when($sort === 'rating', fn ($query) => $query->orderByDesc('reviews_avg_rating'))
            ->when(! in_array($sort, ['oldest', 'title', 'rating'], true), fn ($query) => $query->latest())
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
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
        $book = DB::transaction(function () use ($request) {
            $book = Book::create([
                'user_id' => $request->user()->id,
                ...$request->safe()->except('genres'),
            ]);

            $book->genres()->sync($request->validated('genres'));

            return $book;
        });

        return redirect()->route('books.show', $book)->with('success', '書籍情報を登録しました。');
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
        DB::transaction(function () use ($request, $book) {
            $book->update($request->safe()->except('genres'));
            $book->genres()->sync($request->validated('genres'));
        });

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
