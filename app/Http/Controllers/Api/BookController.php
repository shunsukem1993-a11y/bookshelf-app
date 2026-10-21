<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexBookRequest;
use App\Http\Resources\BookDetailResource;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得する（認証不要）。
     *
     * キーワード（title・author部分一致）・ジャンルでの絞り込みと
     * ページネーションに対応する。
     */
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        // genre_idが指定されていて存在しない場合は404を返す
        if ($request->filled('genre_id')) {
            Genre::findOrFail($request->integer('genre_id'));
        }

        $books = Book::query()
            ->with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = $request->string('keyword');

                $query->where(
                    fn ($q) => $q->where('title', 'like', "%{$keyword}%")
                        ->orWhere('author', 'like', "%{$keyword}%")
                );
            })
            ->when($request->filled('genre_id'), function ($query) use ($request) {
                $genreId = $request->integer('genre_id');

                $query->whereHas('genres', fn ($q) => $q->where('genres.id', $genreId));
            })
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return BookResource::collection($books);
    }

    /**
     * 書籍詳細を取得する（認証不要）。
     *
     * 存在しないIDが指定された場合は404を返す（Handlerで整形）。
     */
    public function show(Book $book): BookDetailResource
    {
        // レビューは投稿日時の新しい順に表示する
        $book->load([
            'genres',
            'reviews' => fn ($query) => $query->latest(),
            'reviews.user',
        ]);

        return new BookDetailResource($book);
    }
}
