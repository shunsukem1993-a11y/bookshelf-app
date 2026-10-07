<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * レビュー平均評価のTOP10書籍を表示する（ゲストも閲覧可能）。
     *
     * レビューが1件もない書籍は対象外にする。平均評価が同点の場合は、
     * 書籍のid昇順（登録順）で並べる。
     */
    public function index(): View
    {
        $rankedBooks = Book::has('reviews')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderBy('books.id')
            ->take(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
