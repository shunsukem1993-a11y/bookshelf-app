<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSortTest extends TestCase
{
    use RefreshDatabase;

    /**
     * sort=newestの場合、登録日時の新しい順になることを確認する（issue #58）。
     */
    public function test_sort_newest_orders_by_created_at_desc(): void
    {
        $older = Book::factory()->create(['created_at' => now()->subDays(2)]);
        $newer = Book::factory()->create(['created_at' => now()]);

        $response = $this->get(route('books.index', ['sort' => 'newest']));

        $this->assertSame([$newer->id, $older->id], $response->viewData('books')->pluck('id')->toArray());
    }

    /**
     * sort=oldestの場合、登録日時の古い順になることを確認する（issue #58）。
     */
    public function test_sort_oldest_orders_by_created_at_asc(): void
    {
        $older = Book::factory()->create(['created_at' => now()->subDays(2)]);
        $newer = Book::factory()->create(['created_at' => now()]);

        $response = $this->get(route('books.index', ['sort' => 'oldest']));

        $this->assertSame([$older->id, $newer->id], $response->viewData('books')->pluck('id')->toArray());
    }

    /**
     * sort=titleの場合、タイトル昇順になることを確認する（issue #58）。
     */
    public function test_sort_title_orders_alphabetically(): void
    {
        $b = Book::factory()->create(['title' => 'b_book']);
        $a = Book::factory()->create(['title' => 'a_book']);

        $response = $this->get(route('books.index', ['sort' => 'title']));

        $this->assertSame([$a->id, $b->id], $response->viewData('books')->pluck('id')->toArray());
    }

    /**
     * sort=ratingの場合、平均評価の高い順になることを確認する（issue #58）。
     */
    public function test_sort_rating_orders_by_average_rating_desc(): void
    {
        $low = Book::factory()->create();
        Review::factory()->create(['book_id' => $low->id, 'rating' => 2]);

        $high = Book::factory()->create();
        Review::factory()->create(['book_id' => $high->id, 'rating' => 5]);

        $response = $this->get(route('books.index', ['sort' => 'rating']));

        $this->assertSame([$high->id, $low->id], $response->viewData('books')->pluck('id')->toArray());
    }

    /**
     * sort=ratingの場合、レビューがない書籍は評価の有無に関わらず最後に表示されることを確認する（issue #58）。
     */
    public function test_sort_rating_places_books_without_reviews_last(): void
    {
        $noReview = Book::factory()->create();

        $withReview = Book::factory()->create();
        Review::factory()->create(['book_id' => $withReview->id, 'rating' => 1]);

        $response = $this->get(route('books.index', ['sort' => 'rating']));

        $this->assertSame([$withReview->id, $noReview->id], $response->viewData('books')->pluck('id')->toArray());
    }

    /**
     * ページネーション時に検索条件（keyword/sort）がリンクに維持されることを確認する（issue #58）。
     */
    public function test_pagination_links_preserve_search_conditions(): void
    {
        Book::factory()->count(15)->create(['title' => '共通タイトル']);

        $response = $this->get(route('books.index', ['keyword' => '共通', 'sort' => 'title']));

        $nextPageUrl = $response->viewData('books')->nextPageUrl();
        $this->assertStringContainsString('keyword=', $nextPageUrl);
        $this->assertStringContainsString('sort=title', $nextPageUrl);
    }

    /**
     * 2ページ目に遷移してもkeyword絞り込みが維持され、該当する書籍のみ表示されることを確認する（issue #58）。
     */
    public function test_second_page_still_applies_keyword_filter(): void
    {
        Book::factory()->count(12)->create(['title' => '対象タイトル']);
        Book::factory()->count(3)->create(['title' => '対象外タイトル']);

        $response = $this->get(route('books.index', ['keyword' => '対象タイトル', 'page' => 2]));

        $books = $response->viewData('books');
        $this->assertSame(12, $books->total());
        $this->assertCount(2, $books); // 12件中、1ページ目10件→2ページ目2件
    }
}
