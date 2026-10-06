<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * 書籍のお気に入りを切り替える（認証必須）。登録済みなら解除し、未登録なら追加する。
     *
     * 元の画面（書籍詳細・お気に入り一覧）へ戻る。
     */
    public function toggle(Request $request, Book $book): RedirectResponse
    {
        $request->user()->favoriteBooks()->toggle($book->id);

        return redirect()->back();
    }

    /**
     * お気に入り一覧を表示する（認証必須）。登録日時の新しい順に10件/ページで表示する。
     */
    public function index(Request $request): View
    {
        $books = $request->user()->favoriteBooks()
            ->orderByPivot('created_at', 'desc')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }
}
