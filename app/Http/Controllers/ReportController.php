<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する（認証必須）。
     *
     * ログインユーザー自身のレビューを基に、基本サマリー・評価分布・
     * 高評価書籍TOP5・ジャンル別評価傾向TOP5を算出する。
     */
    public function index(Request $request): View
    {
        $reviews = Review::with('book.genres')
            ->where('user_id', $request->user()->id)
            ->get();

        $stats = [
            'summary' => $this->buildSummary($reviews),
            'rating_distribution' => $this->buildRatingDistribution($reviews),
            'top_rated_books' => $this->buildTopRatedBooks($reviews),
            'genre_ratings' => $this->buildGenreRatings($reviews),
        ];

        return view('reports.index', compact('stats'));
    }

    /**
     * 基本サマリー（総レビュー数・読了冊数・平均評価点）を算出する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return array<string, int|float>
     */
    private function buildSummary(Collection $reviews): array
    {
        return [
            'total_reviews' => $reviews->count(),
            'books_read' => $reviews->pluck('book_id')->unique()->count(),
            'average_rating' => $reviews->isEmpty() ? 0 : round($reviews->avg('rating'), 1),
        ];
    }

    /**
     * 評価（1〜5）ごとの件数を算出する。
     *
     * @param  Collection<int, Review>  $reviews
     * @return Collection<int, int>
     */
    private function buildRatingDistribution(Collection $reviews): Collection
    {
        return collect(range(1, 5))
            ->map(fn (int $rating) => $reviews->where('rating', $rating)->count());
    }

    /**
     * 4星以上の書籍を評価の高い順に最大5件算出する（同一書籍への複数レビューは平均を使用）。
     *
     * @param  Collection<int, Review>  $reviews
     * @return array<int, array<string, mixed>>
     */
    private function buildTopRatedBooks(Collection $reviews): array
    {
        return $reviews
            ->groupBy('book_id')
            ->map(fn (Collection $bookReviews) => [
                'id' => $bookReviews->first()->book->id,
                'title' => $bookReviews->first()->book->title,
                'author' => $bookReviews->first()->book->author,
                'rating' => (int) round($bookReviews->avg('rating')),
            ])
            ->filter(fn (array $book) => $book['rating'] >= 4)
            ->sortByDesc('rating')
            ->take(5)
            ->values()
            ->all();
    }

    /**
     * ジャンルごとの平均評価・件数を高い順に最大5件算出する。
     *
     * 1件のレビューは、対象書籍に紐づく全ジャンルの集計に反映される。
     *
     * @param  Collection<int, Review>  $reviews
     * @return array<int, array<string, mixed>>
     */
    private function buildGenreRatings(Collection $reviews): array
    {
        return $reviews
            ->flatMap(fn (Review $review) => $review->book->genres->map(fn ($genre) => [
                'id' => $genre->id,
                'name' => $genre->name,
                'rating' => $review->rating,
            ]))
            ->groupBy('id')
            ->map(fn (Collection $entries) => [
                'id' => $entries->first()['id'],
                'name' => $entries->first()['name'],
                'count' => $entries->count(),
                'average_rating' => round($entries->avg('rating'), 1),
            ])
            ->sortByDesc('average_rating')
            ->take(5)
            ->values()
            ->all();
    }
}
