<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// 書籍一覧（トップ）。"/" と "/books" の両方でアクセス可能。ゲストも閲覧可。
Route::get('/', [BookController::class, 'index']);
Route::get('/books', [BookController::class, 'index'])->name('books.index');

/**
 * 書籍の登録・編集・削除。認証必須（未認証時は/loginにリダイレクト）。
 *
 * 注意: "/books/create" は下にある "/books/{book}"（books.show、wildcard）より
 *       前に登録すること。順序が逆になるとワイルドカードに食われてしまう
 *       （"/books/create" が書籍ID="create"として解釈される）。
 */
Route::middleware('auth')->group(function () {
    // 書籍登録フォームを表示する（認証必須）。
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');

    // 書籍を登録する（認証必須）。
    Route::post('/books', [BookController::class, 'store'])->name('books.store');

    // 書籍編集フォームを表示する（作成者本人のみ。BookPolicy@updateで認可）。
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');

    // 書籍を更新する（作成者本人のみ。BookPolicy@updateで認可）。
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');

    // 書籍を削除する（作成者本人のみ。BookPolicy@deleteで認可）。
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');

    // 書籍にレビューを投稿する（認証必須）。
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    // レビュー編集フォームを表示する（投稿者本人のみ。ReviewPolicy@updateで認可）。
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');

    // レビューを更新する（投稿者本人のみ。UpdateReviewRequestで認可）。
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');

    // レビューを削除する（投稿者本人のみ。ReviewPolicy@deleteで認可）。
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // ジャンル一覧（書籍数付き。登録順）。
    Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');

    // ジャンル登録フォーム。仮ルート（実装はIssue #37）。
    // 注意: "/genres/create" は下にある "/genres/{genre}" より前に登録すること。
    Route::get('/genres/create', fn () => '準備中')->name('genres.create');

    // ジャンル詳細（紐づく書籍を10件/ページ、登録順）。
    Route::get('/genres/{genre}', [GenreController::class, 'show'])->name('genres.show');

    // ジャンル編集フォーム。仮ルート（実装はIssue #37）。
    Route::get('/genres/{genre}/edit', fn (Genre $genre) => '準備中')->name('genres.edit');

    // ジャンル削除。仮ルート（実装はIssue #38）。
    Route::delete('/genres/{genre}', fn (Genre $genre) => '準備中')->name('genres.destroy');
});

// 書籍詳細。ゲストも閲覧可。存在しないIDはLaravel標準の404ページを返す。
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// --- 以下は別Issueで実装予定の仮ルート（books/show.blade.phpのroute()解決用） ---
Route::post('/books/{book}/favorites', fn (Book $book) => '準備中')->name('favorites.toggle');
Route::post('/reviews/{review}/like', fn (Review $review) => '準備中')->name('reviews.like');
Route::get('/favorites', fn () => '準備中')->name('favorites.index');
Route::get('/ranking', fn () => '準備中')->name('ranking.index');
