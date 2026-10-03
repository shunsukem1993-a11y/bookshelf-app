<?php

use App\Http\Controllers\BookController;
use App\Models\Book;
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
 * - GET    /books/create      書籍登録フォームを表示する
 * - POST   /books              書籍を登録する
 * - GET    /books/{book}/edit  書籍編集フォームを表示する（作成者本人のみ。BookPolicy@updateで認可）
 * - PUT    /books/{book}       書籍を更新する（作成者本人のみ。BookPolicy@updateで認可）
 * - DELETE /books/{book}       書籍を削除する（作成者本人のみ。BookPolicy@deleteで認可）
 *
 * 注意: "/books/create" は下にある "/books/{book}"（books.show、wildcard）より
 *       前に登録すること。順序が逆になるとワイルドカードに食われてしまう
 *       （"/books/create" が書籍ID="create"として解釈される）。
 */
Route::middleware('auth')->group(function () {
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
});

// 書籍詳細。ゲストも閲覧可。存在しないIDはLaravel標準の404ページを返す。
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// --- 以下は別Issueで実装予定の仮ルート（books/show.blade.phpのroute()解決用） ---
Route::post('/books/{book}/favorites', fn (Book $book) => '準備中')->name('favorites.toggle');
Route::post('/books/{book}/reviews', fn (Book $book) => '準備中')->name('reviews.store');
Route::post('/reviews/{review}/like', fn (Review $review) => '準備中')->name('reviews.like');
Route::get('/genres', fn () => '準備中')->name('genres.index');
Route::get('/favorites', fn () => '準備中')->name('favorites.index');
Route::get('/ranking', fn () => '準備中')->name('ranking.index');
