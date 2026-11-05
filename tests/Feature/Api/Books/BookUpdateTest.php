<?php

namespace Tests\Feature\Api\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 有効なデータで更新でき、200・DB更新・ジャンルのsyncが行われることを確認する（API-19）。
     */
    public function test_update_modifies_book_with_valid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);
        $oldGenre = Genre::factory()->create();
        $book->genres()->attach($oldGenre);
        $newGenre = Genre::factory()->create();

        $response = $this->putJson(route('api.books.update', $book), [
            'user_id' => $user->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'published_date' => '2021-01-01',
            'genre_id' => [$newGenre->id],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.title', '更新後タイトル');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
        ]);

        $book->refresh();
        $this->assertEqualsCanonicalizing([$newGenre->id], $book->genres->pluck('id')->toArray());
    }

    /**
     * 更新時、自身のISBNを変更せず送信しても一意性エラーにならないことを確認する（API-20）。
     */
    public function test_update_allows_keeping_own_isbn(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id, 'isbn' => '3333333333333']);
        $genre = Genre::factory()->create();

        $response = $this->putJson(route('api.books.update', $book), [
            'user_id' => $user->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '3333333333333',
            'published_date' => '2021-01-01',
            'genre_id' => [$genre->id],
        ]);

        $response->assertOk();
        $response->assertJsonMissingValidationErrors('isbn');
    }

    /**
     * PATCHメソッドでも更新できることを確認する（API-21）。
     */
    public function test_update_accepts_patch_method(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);
        $genre = Genre::factory()->create();

        $response = $this->patchJson(route('api.books.update', $book), [
            'user_id' => $user->id,
            'title' => 'PATCHで更新',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => '2021-01-01',
            'genre_id' => [$genre->id],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'PATCHで更新']);
    }

    /**
     * 存在しない書籍IDを指定すると404とエラーメッセージが返ることを確認する（API-22）。
     */
    public function test_update_returns_404_when_book_does_not_exist(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->putJson(route('api.books.update', ['book' => 999999]), [
            'user_id' => $user->id,
            'title' => 'X',
            'author' => 'Y',
            'isbn' => '4444444444444',
            'published_date' => '2020-01-01',
            'genre_id' => [$genre->id],
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('error', '書籍情報が見つかりませんでした。');
    }
}
