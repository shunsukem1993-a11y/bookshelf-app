<?php

namespace Tests\Feature\Api\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍一覧がJSON形式で取得でき、各要素に必要な項目が含まれることを確認する（API-01）。
     */
    public function test_index_returns_books_in_json_with_expected_fields(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);
        $book->genres()->attach($genre);

        $response = $this->getJson(route('api.books.index'));

        $response->assertOk();
        $response->assertJsonPath('data.0.id', $book->id);
        $response->assertJsonPath('data.0.title', $book->title);
        $response->assertJsonPath('data.0.author', $book->author);
        $response->assertJsonPath('data.0.image_url', $book->image_url);
        $response->assertJsonPath('data.0.genres.0.id', $genre->id);
        $response->assertJsonPath('data.0.genres.0.name', '小説');
        $response->assertJsonPath('data.0.review_count', 0);
        $response->assertJsonPath('data.0.average_rating', null);
    }

    /**
     * レビューがある書籍はreview_countとaverage_ratingが正しく算出されることを確認する（API-02）。
     */
    public function test_index_shows_review_count_and_average_rating(): void
    {
        $book = Book::factory()->create();
        Review::factory()->create(['book_id' => $book->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $book->id, 'rating' => 3]);

        $response = $this->getJson(route('api.books.index'));

        $response->assertJsonPath('data.0.review_count', 2);
        // PHPのjson_encodeは整数値になる浮動小数点数を"4"のように出力するため、
        // JSONデコード後は4.0ではなく整数4として比較する。
        $response->assertJsonPath('data.0.average_rating', 4);
    }

    /**
     * keywordがtitleに部分一致する書籍のみ返ることを確認する（API-03）。
     */
    public function test_index_filters_by_keyword_matching_title(): void
    {
        Book::factory()->create(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        Book::factory()->create(['title' => '銀河鉄道の夜', 'author' => '宮沢賢治']);

        $response = $this->getJson(route('api.books.index', ['keyword' => '猫']));

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', '吾輩は猫である');
    }

    /**
     * keywordがauthorに部分一致する書籍のみ返ることを確認する（API-04）。
     */
    public function test_index_filters_by_keyword_matching_author(): void
    {
        Book::factory()->create(['title' => '吾輩は猫である', 'author' => '夏目漱石']);
        Book::factory()->create(['title' => '銀河鉄道の夜', 'author' => '宮沢賢治']);

        $response = $this->getJson(route('api.books.index', ['keyword' => '賢治']));

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', '銀河鉄道の夜');
    }

    /**
     * keywordが一致しない場合、dataが空配列になることを確認する（API-05）。
     */
    public function test_index_returns_empty_data_when_keyword_does_not_match(): void
    {
        Book::factory()->create(['title' => '吾輩は猫である', 'author' => '夏目漱石']);

        $response = $this->getJson(route('api.books.index', ['keyword' => '存在しないキーワード']));

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    /**
     * genre_idで絞り込むと、該当ジャンルを持つ書籍のみ返ることを確認する（API-06）。
     */
    public function test_index_filters_by_genre_id(): void
    {
        $novel = Genre::factory()->create(['name' => '小説']);
        $sf = Genre::factory()->create(['name' => 'SF']);

        $bookWithNovel = Book::factory()->create();
        $bookWithNovel->genres()->attach($novel);

        $bookWithSf = Book::factory()->create();
        $bookWithSf->genres()->attach($sf);

        $response = $this->getJson(route('api.books.index', ['genre_id' => $novel->id]));

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $bookWithNovel->id);
    }

    /**
     * per_page指定時、指定件数でページネーションされることを確認する（API-07）。
     */
    public function test_index_respects_per_page_parameter(): void
    {
        Book::factory()->count(5)->create();

        $response = $this->getJson(route('api.books.index', ['per_page' => 2]));

        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 5);
        $response->assertJsonPath('meta.last_page', 3);
    }

    /**
     * 2ページ目を取得すると、2ページ目のデータが返ることを確認する（API-08）。
     */
    public function test_index_returns_second_page(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson(route('api.books.index', ['per_page' => 2, 'page' => 2]));

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.current_page', 2);
    }

    /**
     * 存在しないgenre_idを指定すると422とエラーメッセージが返ることを確認する（API-09）。
     */
    public function test_index_returns_422_when_genre_id_does_not_exist(): void
    {
        $response = $this->getJson(route('api.books.index', ['genre_id' => 999999]));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.genre_id.0', '指定されたジャンルが見つかりませんでした。');
    }

    /**
     * genre_idが非整数の場合422とエラーメッセージが返ることを確認する（API-10）。
     */
    public function test_index_returns_422_when_genre_id_is_not_integer(): void
    {
        $response = $this->getJson(route('api.books.index', ['genre_id' => 'abc']));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.genre_id.0', 'ジャンルIDは整数で指定してください。');
    }

    /**
     * per_page100超の場合422とエラーメッセージが返ることを確認する（API-11）。
     */
    public function test_index_returns_422_when_per_page_exceeds_100(): void
    {
        $response = $this->getJson(route('api.books.index', ['per_page' => 101]));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.per_page.0', '1ページあたりの件数は100以下で指定してください。');
    }
}
