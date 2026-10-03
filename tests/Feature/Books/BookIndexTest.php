<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストが書籍一覧を表示できることを確認する。
     */
    public function test_guest_can_view_book_index(): void
    {
        Book::factory()->create();

        $response = $this->get(route('books.index'));

        $response->assertOk();
    }

    /**
     * 書籍が10件/ページでページネーションされ、11件の場合は2ページ目に1件表示されることを確認する（C-02, B-16）。
     */
    public function test_book_index_is_paginated_by_ten_per_page(): void
    {
        Book::factory()->count(11)->create();

        $page1 = $this->get(route('books.index'));
        $books = $page1->viewData('books');
        $this->assertCount(10, $books);
        $this->assertSame(11, $books->total());
        $this->assertSame(2, $books->lastPage());

        $page2 = $this->get(route('books.index', ['page' => 2]));
        $this->assertCount(1, $page2->viewData('books'));
    }

    /**
     * 書籍が10件の場合は1ページのみになることを確認する（B-15）。
     */
    public function test_book_index_has_single_page_when_exactly_ten_books_exist(): void
    {
        Book::factory()->count(10)->create();

        $response = $this->get(route('books.index'));

        $this->assertSame(1, $response->viewData('books')->lastPage());
    }

    /**
     * 各書籍にジャンル名が表示されることを確認する（C-03）。
     */
    public function test_book_card_shows_its_genres(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create(['name' => 'ミステリー']);
        $book->genres()->attach($genre);

        $response = $this->get(route('books.index'));

        $response->assertSee('ミステリー');
    }

    /**
     * レビューがある書籍は平均評価が表示されることを確認する（C-04）。
     */
    public function test_book_with_reviews_shows_average_rating(): void
    {
        $book = Book::factory()->create();
        Review::factory()->create(['book_id' => $book->id, 'rating' => 4]);
        Review::factory()->create(['book_id' => $book->id, 'rating' => 4]);

        $response = $this->get(route('books.index'));

        $response->assertSee('(4.0)');
    }

    /**
     * レビューが無い書籍は平均評価が表示されず、エラーにならないことを確認する（C-05）。
     */
    public function test_book_without_reviews_does_not_show_rating(): void
    {
        Book::factory()->create();

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertDontSee('(0.0)');
    }

    /**
     * 書籍が作成日時（created_at）の降順で表示されることを確認する（C-06）。
     */
    public function test_books_are_ordered_by_latest_first(): void
    {
        $older = Book::factory()->create(['created_at' => now()->subDays(2)]);
        $newer = Book::factory()->create(['created_at' => now()->subDay()]);
        $newest = Book::factory()->create(['created_at' => now()]);

        $response = $this->get(route('books.index'));

        $this->assertSame(
            [$newest->id, $newer->id, $older->id],
            $response->viewData('books')->pluck('id')->toArray()
        );
    }
}
