<?php

use App\Http\Controllers\BookController;
use App\Models\Book;
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

// --- 以下は別Issueで実装予定の仮ルート（navigation.blade.php・books/index.blade.phpのroute()解決用） ---
Route::get('/books/create', fn () => '準備中')->name('books.create');
Route::get('/books/{book}', fn (Book $book) => '準備中')->name('books.show');
Route::get('/genres', fn () => '準備中')->name('genres.index');
Route::get('/favorites', fn () => '準備中')->name('favorites.index');
Route::get('/ranking', fn () => '準備中')->name('ranking.index');
