<?php

namespace Tests\Feature\Api\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証でPUTすると401になり、DBが更新されないことを確認する（★AP06）。
     */
    public function test_guest_cannot_update_book(): void
    {
        $book = Book::factory()->create(['title' => '元のタイトル']);

        $response = $this->putJson(route('api.books.update', $book), ['title' => '更新試行']);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Unauthenticated.');
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '元のタイトル']);
    }

    /**
     * 未認証でDELETEすると401になり、DBから削除されないことを確認する（★AP06）。
     */
    public function test_guest_cannot_destroy_book(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson(route('api.books.destroy', $book));

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Unauthenticated.');
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    /**
     * 所有者本人は自分の書籍を更新できることを確認する（★AP06）。
     */
    public function test_owner_can_update_own_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $genre = Genre::factory()->create();
        Sanctum::actingAs($owner);

        $response = $this->putJson(route('api.books.update', $book), [
            'title' => '所有者による更新',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => '2020-01-01',
            'genre_id' => [$genre->id],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '所有者による更新']);
    }

    /**
     * 所有者本人は自分の書籍を削除できることを確認する（★AP06）。
     */
    public function test_owner_can_destroy_own_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $response = $this->deleteJson(route('api.books.destroy', $book));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /**
     * 他ユーザーが更新しようとすると、入力内容（有効・無効）に関係なく403になり、
     * DBが更新されないことを確認する（★AP06）。
     *
     * UpdateBookRequestの認可（authorize）はバリデーションより先に実行されるため、
     * 無効な入力でもバリデーションエラー（422）ではなく403が返る。
     *
     * @dataProvider otherUserUpdateOverridesProvider
     *
     * @param  array<string, mixed>  $overrides
     */
    public function test_other_user_cannot_update_book(array $overrides): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id, 'title' => '元のタイトル']);
        $genre = Genre::factory()->create();
        Sanctum::actingAs($otherUser);

        $payload = array_merge($this->validPayload($book, $genre), $overrides);

        $response = $this->putJson(route('api.books.update', $book), $payload);

        $response->assertStatus(403);
        $response->assertJsonPath('error', 'この操作を実行する権限がありません。');
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '元のタイトル']);
    }

    /**
     * 他ユーザーの更新で、有効な入力に上書きする値の一覧（有効な値は上書きなし、無効な値はタイトルを空にする）。
     *
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function otherUserUpdateOverridesProvider(): array
    {
        return [
            '有効な値' => [[]],
            '無効な値（タイトルが空）' => [['title' => '']],
        ];
    }

    /**
     * 他ユーザーが削除しようとすると403になり、DBから削除されないことを確認する（★AP06）。
     */
    public function test_other_user_cannot_destroy_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson(route('api.books.destroy', $book));

        $response->assertStatus(403);
        $response->assertJsonPath('error', 'この操作を実行する権限がありません。');
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    /**
     * 更新用の有効なリクエストボディを生成する。
     *
     * @return array<string, mixed>
     */
    private function validPayload(Book $book, Genre $genre): array
    {
        return [
            'title' => '有効なタイトル',
            'author' => '有効な著者',
            'isbn' => $book->isbn,
            'published_date' => '2020-01-01',
            'genre_id' => [$genre->id],
        ];
    }
}
