<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use App\Models\Genre;
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

    // ジャンル登録フォームを表示する（認証必須）。
    // 注意: "/genres/create" は下にある "/genres/{genre}" より前に登録すること。
    Route::get('/genres/create', [GenreController::class, 'create'])->name('genres.create');

    // ジャンルを登録する（認証必須。成功時はジャンル一覧へ）。
    Route::post('/genres', [GenreController::class, 'store'])->name('genres.store');

    // ジャンル詳細（紐づく書籍を10件/ページ、登録順）。
    Route::get('/genres/{genre}', [GenreController::class, 'show'])->name('genres.show');

    // ジャンル編集フォームを表示する（認証必須）。
    Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])->name('genres.edit');

    // ジャンルを更新する（認証必須。成功時はジャンル一覧へ）。
    Route::put('/genres/{genre}', [GenreController::class, 'update'])->name('genres.update');

    // ジャンル削除。仮ルート（実装はIssue #38）。
    Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->name('genres.destroy');

    // 書籍のお気に入りを切り替える（認証必須。登録済みなら解除、未登録なら追加）。
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // お気に入り一覧（認証必須。登録日時の新しい順に10件/ページ）。
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');

    // レビューのいいねを切り替える（認証必須。登録済みなら解除、未登録なら追加）。
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])->name('reviews.like');
});

// 書籍詳細。ゲストも閲覧可。存在しないIDはLaravel標準の404ページを返す。
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// ランキング（レビュー平均評価TOP10）。レビューが1件もない書籍は対象外。ゲストも閲覧可。
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
