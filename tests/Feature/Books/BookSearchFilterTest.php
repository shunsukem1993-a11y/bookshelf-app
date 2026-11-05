<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * keywordがtitleに部分一致する書籍のみ返ることを確認する（issue #57）。
     */
    public function test_keyword_filters_by_title_partial_match(): void
    {
        $matched = Book::factory()->create(['title' => '吾輩は猫である']);
        Book::factory()->create(['title' => '坊っちゃん']);

        $response = $this->get(route('books.index', ['keyword' => '猫']));

        $books = $response->viewData('books');
        $this->assertSame([$matched->id], $books->pluck('id')->toArray());
    }

    /**
     * keywordがauthorに部分一致する書籍のみ返ることを確認する（issue #57）。
     */
    public function test_keyword_filters_by_author_partial_match(): void
    {
        $matched = Book::factory()->create(['author' => '夏目漱石']);
        Book::factory()->create(['author' => '芥川龍之介']);

        $response = $this->get(route('books.index', ['keyword' => '漱石']));

        $books = $response->viewData('books');
        $this->assertSame([$matched->id], $books->pluck('id')->toArray());
    }

    /**
     * keywordに一致する書籍がない場合は空になることを確認する（issue #57）。
     */
    public function test_keyword_with_no_match_returns_empty(): void
    {
        Book::factory()->create(['title' => '吾輩は猫である', 'author' => '夏目漱石']);

        $response = $this->get(route('books.index', ['keyword' => '存在しないキーワード']));

        $this->assertTrue($response->viewData('books')->isEmpty());
    }

    /**
     * genreで絞り込んだ場合、そのジャンルに紐づく書籍のみ返ることを確認する（issue #57）。
     */
    public function test_genre_filter_returns_only_books_in_that_genre(): void
    {
        $genre = Genre::factory()->create();
        $matched = Book::factory()->create();
        $matched->genres()->attach($genre);
        Book::factory()->create(); // 紐付きなし

        $response = $this->get(route('books.index', ['genre' => $genre->id]));

        $books = $response->viewData('books');
        $this->assertSame([$matched->id], $books->pluck('id')->toArray());
    }

    /**
     * keywordとgenreを組み合わせた場合、両方の条件を満たす書籍のみ返ることを確認する（issue #57）。
     */
    public function test_keyword_and_genre_combined_filter(): void
    {
        $genre = Genre::factory()->create();

        $matched = Book::factory()->create(['title' => '吾輩は猫である']);
        $matched->genres()->attach($genre);

        // タイトルは一致するがジャンルが異なる
        $wrongGenre = Book::factory()->create(['title' => '猫の歴史']);
        $wrongGenre->genres()->attach(Genre::factory()->create());

        // ジャンルは一致するがタイトルが異なる
        $wrongKeyword = Book::factory()->create(['title' => '坊っちゃん']);
        $wrongKeyword->genres()->attach($genre);

        $response = $this->get(route('books.index', ['keyword' => '猫', 'genre' => $genre->id]));

        $books = $response->viewData('books');
        $this->assertSame([$matched->id], $books->pluck('id')->toArray());
    }
}
