<?php

namespace Tests\Unit\Services;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use App\Services\ReadingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingReportServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レビューが0件のユーザーは、全ての統計項目が安全な既定値になることを確認する。
     */
    public function test_build_report_returns_safe_defaults_for_user_with_no_reviews(): void
    {
        $user = User::factory()->create();

        $report = app(ReadingReportService::class)->buildReport($user);

        $this->assertSame(0, $report['summary']['total_reviews']);
        $this->assertSame(0, $report['summary']['books_read']);
        $this->assertSame(0, $report['summary']['average_rating']);
        $this->assertSame([0, 0, 0, 0, 0], $report['rating_distribution']->values()->toArray());
        $this->assertSame([], $report['top_rated_books']);
        $this->assertSame([], $report['genre_ratings']);
    }

    /**
     * サマリー・評価分布・高評価書籍・ジャンル別評価傾向が、
     * 対象ユーザー自身のレビューのみから正しく算出されることを確認する
     * （他ユーザーのレビューは集計に含まれない）。
     */
    public function test_build_report_aggregates_own_reviews_only(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create();
        $book->genres()->attach($genre);
        Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id, 'rating' => 5]);

        $otherBook = Book::factory()->create();
        Review::factory()->create(['user_id' => $otherUser->id, 'book_id' => $otherBook->id, 'rating' => 1]);

        $report = app(ReadingReportService::class)->buildReport($user);

        $this->assertSame(1, $report['summary']['total_reviews']);
        $this->assertSame(1, $report['summary']['books_read']);
        $this->assertSame(5.0, $report['summary']['average_rating']);
        $this->assertSame([0, 0, 0, 0, 1], $report['rating_distribution']->values()->toArray());
        $this->assertCount(1, $report['top_rated_books']);
        $this->assertSame($book->id, $report['top_rated_books'][0]['id']);
        $this->assertCount(1, $report['genre_ratings']);
        $this->assertSame($genre->id, $report['genre_ratings'][0]['id']);
    }
}
