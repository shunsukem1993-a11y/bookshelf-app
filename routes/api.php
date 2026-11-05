<?php

use App\Http\Controllers\Api\BookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 公開API（書籍一覧・詳細取得・登録・更新・削除）。
// 読み取り系（一覧・詳細）は認証不要。書き込み系（登録・更新・削除）は★応用でSanctumトークン認証を必須化。
Route::prefix('v1')->group(function () {
    // 書籍一覧を取得する（キーワード・ジャンル絞り込み、ページネーション対応）。認証不要。
    Route::get('/books', [BookController::class, 'index'])->name('api.books.index');

    // 書籍詳細を取得する。存在しないIDは404を返す。認証不要。
    Route::get('/books/{book}', [BookController::class, 'show'])->name('api.books.show');

    // ★ 書き込み系はSanctumトークン認証を必須化（未認証時は401）。
    Route::middleware('auth:sanctum')->group(function () {
        // 書籍を新規登録する。登録者は認証済みユーザーとする。
        Route::post('/books', [BookController::class, 'store'])->name('api.books.store');

        // 書籍を更新する。所有者以外は403、存在しないIDは404を返す。
        Route::match(['put', 'patch'], '/books/{book}', [BookController::class, 'update'])->name('api.books.update');

        // 書籍を削除する。所有者以外は403、存在しないIDは404を返す。
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('api.books.destroy');
    });
});
