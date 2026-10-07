<?php

namespace Tests\Feature\Ranking;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストがランキング画面を表示でき、200が返ることを確認する。
     */
    public function test_guest_can_view_ranking(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
    }

    /**
     * 平均評価の高い順に書籍が表示されることを確認する。
     */
    public function test_books_are_ranked_by_average_rating_descending(): void
    {
        $low = Book::factory()->create(['title' => '評価の低い本']);
        Review::factory()->create(['book_id' => $low->id, 'rating' => 3]);

        $high = Book::factory()->create(['title' => '評価の高い本']);
        Review::factory()->create(['book_id' => $high->id, 'rating' => 5]);

        $middle = Book::factory()->create(['title' => '評価が中間の本']);
        Review::factory()->create(['book_id' => $middle->id, 'rating' => 4]);

        $response = $this->get(route('ranking.index'));

        $response->assertSeeInOrder(['評価の高い本', '評価が中間の本', '評価の低い本']);
    }

    /**
     * レビューがある書籍は表示され、レビューが0件の書籍は表示されないことを確認する。
     */
    public function test_books_without_reviews_are_excluded(): void
    {
        $reviewed = Book::factory()->create(['title' => 'レビューがある本']);
        Review::factory()->create(['book_id' => $reviewed->id, 'rating' => 4]);

        Book::factory()->create(['title' => 'レビューがない本']);

        $response = $this->get(route('ranking.index'));

        $response->assertSee('レビューがある本');
        $response->assertDontSee('レビューがない本');
    }

    /**
     * 平均評価が同点の場合は書籍id昇順で並び、TOP10を超える分は表示されないことを確認する。
     */
    public function test_ties_are_broken_by_book_id_and_limited_to_top_ten(): void
    {
        $books = Book::factory()->count(11)->sequence(
            ...collect(range(1, 11))->map(fn (int $n) => ['title' => "同点の本{$n}"])->all()
        )->create();

        foreach ($books as $book) {
            Review::factory()->create(['book_id' => $book->id, 'rating' => 5]);
        }

        $response = $this->get(route('ranking.index'));

        $response->assertSeeInOrder($books->take(10)->pluck('title')->all());
        $response->assertDontSee($books->last()->title);
    }
}
