<?php

namespace Tests\Feature\Api\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 削除に成功すると204が返り、DBから削除されることを確認する（API-23）。
     */
    public function test_destroy_deletes_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson(route('api.books.destroy', $book));

        $response->assertStatus(204);
        $response->assertNoContent();
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /**
     * 削除時、関連データ（ジャンル紐付け・お気に入り・レビュー）がカスケードで
     * 削除されることを確認する（API-24）。
     */
    public function test_destroy_cascades_related_data(): void
    {
        $book = Book::factory()->create();

        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);

        $favoriteUser = User::factory()->create();
        $book->favoritedByUsers()->attach($favoriteUser);

        Review::factory()->create(['book_id' => $book->id]);

        $this->deleteJson(route('api.books.destroy', $book));

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('favorites', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('reviews', ['book_id' => $book->id]);
    }

    /**
     * 存在しない書籍IDを指定すると404とエラーメッセージが返ることを確認する（API-25）。
     */
    public function test_destroy_returns_404_when_book_does_not_exist(): void
    {
        $response = $this->deleteJson(route('api.books.destroy', ['book' => 999999]));

        $response->assertStatus(404);
        $response->assertJsonPath('error', '書籍情報が見つかりませんでした。');
    }
}
