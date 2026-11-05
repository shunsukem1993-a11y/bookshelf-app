<?php

namespace Tests\Feature\Reports;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証でアクセスすると/loginにリダイレクトされることを確認する（issue #62）。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * レビューが0件のユーザーは、全ての統計項目が安全な既定値になることを確認する（issue #62）。
     */
    public function test_user_with_no_reviews_sees_safe_defaults(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $stats = $response->viewData('stats');

        $this->assertSame(0, $stats['summary']['total_reviews']);
        $this->assertSame(0, $stats['summary']['books_read']);
        $this->assertSame(0, $stats['summary']['average_rating']);
        $this->assertSame([0, 0, 0, 0, 0], $stats['rating_distribution']->values()->toArray());
        $this->assertSame([], $stats['top_rated_books']);
        $this->assertSame([], $stats['genre_ratings']);

        $response->assertSee('4星以上の書籍がありません');
        $response->assertSee('ジャンルが設定された書籍のレビューがありません');
    }

    /**
     * 基本サマリー（総レビュー数・読了冊数・平均評価点）が正しく算出されることを確認する（issue #62）。
     */
    public function test_summary_is_calculated_correctly(): void
    {
        $user = User::factory()->create();
        $bookA = Book::factory()->create();
        $bookB = Book::factory()->create();

        Review::factory()->create(['user_id' => $user->id, 'book_id' => $bookA->id, 'rating' => 4]);
        Review::factory()->create(['user_id' => $user->id, 'book_id' => $bookA->id, 'rating' => 2]); // 同一書籍の2件目
        Review::factory()->create(['user_id' => $user->id, 'book_id' => $bookB->id, 'rating' => 5]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $stats = $response->viewData('stats');
        $this->assertSame(3, $stats['summary']['total_reviews']);
        $this->assertSame(2, $stats['summary']['books_read']); // ユニーク書籍数
        $this->assertSame(3.7, $stats['summary']['average_rating']); // (4+2+5)/3 ≈ 3.7
    }

    /**
     * 評価分布（1〜5星ごとの件数）が正しく算出されることを確認する（issue #62）。
     */
    public function test_rating_distribution_is_calculated_correctly(): void
    {
        $user = User::factory()->create();

        Review::factory()->create(['user_id' => $user->id, 'rating' => 5]);
        Review::factory()->create(['user_id' => $user->id, 'rating' => 5]);
        Review::factory()->create(['user_id' => $user->id, 'rating' => 3]);
        Review::factory()->create(['user_id' => $user->id, 'rating' => 1]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $this->assertSame([1, 0, 1, 0, 2], $response->viewData('stats')['rating_distribution']->values()->toArray());
    }

    /**
     * 高評価書籍TOP5が4星以上・評価の高い順・最大5件になることを確認する（issue #62）。
     */
    public function test_top_rated_books_are_limited_to_five_and_sorted_descending(): void
    {
        $user = User::factory()->create();

        // 4星未満は除外される
        $excluded = Book::factory()->create(['title' => '除外される本']);
        Review::factory()->create(['user_id' => $user->id, 'book_id' => $excluded->id, 'rating' => 3]);

        // 6冊分の4星以上レビューを作成し、5件に絞られることを確認する
        $books = Book::factory()->count(6)->create();
        foreach ($books as $index => $book) {
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => 4 + ($index % 2), // 4か5
            ]);
        }

        $response = $this->actingAs($user)->get(route('reports.index'));

        $topRatedBooks = $response->viewData('stats')['top_rated_books'];
        $this->assertCount(5, $topRatedBooks);
        $ratings = array_column($topRatedBooks, 'rating');
        $this->assertSame($ratings, collect($ratings)->sortDesc()->values()->all());
        $this->assertNotContains('除外される本', array_column($topRatedBooks, 'title'));
    }

    /**
     * 同一書籍に複数レビューがある場合、評価の平均が使用されることを確認する（issue #62）。
     */
    public function test_top_rated_books_uses_average_rating_for_duplicate_reviews(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['title' => '複数レビュー本']);

        Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id, 'rating' => 5]);
        Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id, 'rating' => 3]); // 平均4.0

        $response = $this->actingAs($user)->get(route('reports.index'));

        $topRatedBooks = $response->viewData('stats')['top_rated_books'];
        $this->assertCount(1, $topRatedBooks);
        $this->assertSame(4, $topRatedBooks[0]['rating']);
    }

    /**
     * ジャンル別評価傾向TOP5が平均評価の高い順・最大5件になることを確認する（issue #62）。
     */
    public function test_genre_ratings_are_limited_to_five_and_sorted_descending(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(6)->create();

        foreach ($genres as $index => $genre) {
            $book = Book::factory()->create();
            $book->genres()->attach($genre);
            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => ($index % 5) + 1,
            ]);
        }

        $response = $this->actingAs($user)->get(route('reports.index'));

        $genreRatings = $response->viewData('stats')['genre_ratings'];
        $this->assertCount(5, $genreRatings);
        $averages = array_column($genreRatings, 'average_rating');
        $this->assertSame($averages, collect($averages)->sortDesc()->values()->all());
    }

    /**
     * 1冊の書籍が複数ジャンルに紐づく場合、レビューが両方のジャンルの集計に反映されることを確認する（issue #62）。
     */
    public function test_one_review_contributes_to_all_genres_of_its_book(): void
    {
        $user = User::factory()->create();
        $genreA = Genre::factory()->create();
        $genreB = Genre::factory()->create();
        $book = Book::factory()->create();
        $book->genres()->attach([$genreA->id, $genreB->id]);

        Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id, 'rating' => 5]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $genreRatings = collect($response->viewData('stats')['genre_ratings']);
        $this->assertSame(5.0, $genreRatings->firstWhere('id', $genreA->id)['average_rating']);
        $this->assertSame(5.0, $genreRatings->firstWhere('id', $genreB->id)['average_rating']);
    }

    /**
     * 他ユーザーのレビューは自分の統計に含まれないことを確認する（issue #62）。
     */
    public function test_only_own_reviews_are_included_in_statistics(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Review::factory()->create(['user_id' => $otherUser->id, 'rating' => 5]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $this->assertSame(0, $response->viewData('stats')['summary']['total_reviews']);
    }
}
