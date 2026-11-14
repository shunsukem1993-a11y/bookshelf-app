<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IndexBookRequest;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Http\Resources\BookDetailResource;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

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

    /**
     * 書籍を新規登録する（★Sanctumトークン認証が必須。登録者は認証済みユーザーとする）。
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = DB::transaction(function () use ($request) {
            $book = Book::create([
                'user_id' => $request->user()->id,
                ...$request->safe()->except('genre_id'),
            ]);
            $book->genres()->sync($request->validated('genre_id'));

            return $book;
        });

        $book->load(['genres', 'reviews.user']);

        return (new BookDetailResource($book))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * 書籍を更新する（★Sanctumトークン認証＋所有者本人のみ。認可はUpdateBookRequestで行う。
     * 存在しないIDは404を返す）。
     */
    public function update(UpdateBookRequest $request, Book $book): BookDetailResource
    {
        DB::transaction(function () use ($request, $book) {
            $book->update($request->safe()->except('genre_id'));
            $book->genres()->sync($request->validated('genre_id'));
        });

        $book->load([
            'genres',
            'reviews' => fn ($query) => $query->latest(),
            'reviews.user',
        ]);

        return new BookDetailResource($book);
    }

    /**
     * 書籍を削除する（★Sanctumトークン認証＋所有者本人のみ。存在しないIDは404を返す）。
     *
     * 関連データ（book_genre・reviews・favorites）はDBの外部キー制約
     * （cascadeOnDelete）により自動的に削除される。
     */
    public function destroy(Book $book): Response
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }
}
