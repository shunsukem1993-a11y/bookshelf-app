<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する（認証必須）。
     *
     * 仮実装：ナビゲーションの参照先（route('reports.index')）を解決するための
     * 最小限のルートであり、統計データは常に空の状態を返す。
     * 本実装（統計算出処理）はIssue #61で対応する。
     */
    public function index(): View
    {
        $stats = [
            'summary' => [
                'total_reviews' => 0,
                'books_read' => 0,
                'average_rating' => 0,
            ],
            'rating_distribution' => Collection::make([0, 0, 0, 0, 0]),
            'top_rated_books' => [],
            'genre_ratings' => [],
        ];

        return view('reports.index', compact('stats'));
    }
}
