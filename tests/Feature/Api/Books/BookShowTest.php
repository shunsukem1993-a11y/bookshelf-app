<?php

namespace Tests\Feature\Api\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍詳細がJSON形式で取得でき、必要な項目が含まれることを確認する（API-12）。
     */
    public function test_show_returns_book_detail_with_expected_fields(): void
    {
        $book = Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫目線の物語。',
            'image_url' => 'https://example.com/cat.jpg',
        ]);

        $response = $this->getJson(route('api.books.show', $book));

        $response->assertOk();
        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.title', '吾輩は猫である');
        $response->assertJsonPath('data.author', '夏目漱石');
        $response->assertJsonPath('data.isbn', '9784101010014');
        $response->assertJsonPath('data.published_date', '1905-01-01');
        $response->assertJsonPath('data.description', '猫目線の物語。');
        $response->assertJsonPath('data.image_url', 'https://example.com/cat.jpg');
    }

    /**
     * 複数ジャンルを持つ書籍はgenresに複数要素が含まれることを確認する（API-13）。
     */
    public function test_show_includes_multiple_genres(): void
    {
        $book = Book::factory()->create();
        $novel = Genre::factory()->create(['name' => '小説']);
        $sf = Genre::factory()->create(['name' => 'SF']);
        $book->genres()->attach([$novel->id, $sf->id]);

        $response = $this->getJson(route('api.books.show', $book));

        $response->assertJsonCount(2, 'data.genres');
        $response->assertJsonFragment(['id' => $novel->id, 'name' => '小説']);
        $response->assertJsonFragment(['id' => $sf->id, 'name' => 'SF']);
    }

    /**
     * reviewsにuser情報・評価・コメント・投稿日（Y-m-d）が含まれることを確認する（API-14）。
     */
    public function test_show_includes_review_details(): void
    {
        $book = Book::factory()->create();
        $reviewer = User::factory()->create(['name' => '山田太郎']);
        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '最高でした。',
            'created_at' => '2026-10-01 12:00:00',
        ]);

        $response = $this->getJson(route('api.books.show', $book));

        $response->assertJsonPath('data.reviews.0.user.id', $reviewer->id);
        $response->assertJsonPath('data.reviews.0.user.name', '山田太郎');
        $response->assertJsonPath('data.reviews.0.rating', 5);
        $response->assertJsonPath('data.reviews.0.comment', '最高でした。');
        $response->assertJsonPath('data.reviews.0.created_at', '2026-10-01');
    }

    /**
     * 存在しないIDを指定すると404とエラーメッセージが返ることを確認する（API-15）。
     */
    public function test_show_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->getJson(route('api.books.show', ['book' => 999999]));

        $response->assertStatus(404);
        $response->assertJsonPath('error', '書籍情報が見つかりませんでした。');
    }
}
