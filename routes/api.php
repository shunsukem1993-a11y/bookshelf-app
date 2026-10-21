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

// 公開API（書籍一覧・詳細取得）。認証不要。
Route::prefix('v1')->group(function () {
    // 書籍一覧を取得する（キーワード・ジャンル絞り込み、ページネーション対応）。
    Route::get('/books', [BookController::class, 'index'])->name('api.books.index');

    // 書籍詳細を取得する。存在しないIDは404を返す。
    Route::get('/books/{book}', [BookController::class, 'show'])->name('api.books.show');
});
