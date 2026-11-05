<?php

namespace Tests\Feature\Api\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 有効なデータで登録でき、201・DB保存・ジャンル紐付けが行われることを確認する（API-16）。
     */
    public function test_store_creates_book_with_valid_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genres = Genre::factory()->count(2)->create();

        $response = $this->postJson(route('api.books.store'), [
            'title' => '新しい本',
            'author' => '著者名',
            'isbn' => '1234567890123',
            'published_date' => '2020-01-01',
            'description' => '説明文',
            'image_url' => 'https://example.com/image.jpg',
            'genre_id' => $genres->pluck('id')->toArray(),
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', '新しい本');

        $this->assertDatabaseHas('books', [
            'title' => '新しい本',
            'isbn' => '1234567890123',
            'user_id' => $user->id,
        ]);

        $book = Book::where('isbn', '1234567890123')->first();
        $this->assertEqualsCanonicalizing(
            $genres->pluck('id')->toArray(),
            $book->genres->pluck('id')->toArray()
        );
    }

    /**
     * description・image_urlを省略しても登録できることを確認する（API-17）。
     */
    public function test_store_allows_omitting_description_and_image_url(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $response = $this->postJson(route('api.books.store'), [
            'title' => '最小構成の本',
            'author' => '著者名',
            'isbn' => '1111111111111',
            'published_date' => '2020-01-01',
            'genre_id' => [$genre->id],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('books', [
            'isbn' => '1111111111111',
            'description' => null,
            'image_url' => null,
        ]);
    }

    /**
     * 未認証（Authorizationヘッダ無し）でPOSTすると401になり、登録されないことを確認する（★AP06）。
     */
    public function test_guest_cannot_store_book(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->postJson(route('api.books.store'), [
            'title' => '未認証で登録を試みる本',
            'author' => '著者名',
            'isbn' => '2222222222222',
            'published_date' => '2020-01-01',
            'genre_id' => [$genre->id],
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Unauthenticated.');
        $this->assertDatabaseCount('books', 0);
    }

    /**
     * リクエストボディに他ユーザーのuser_idを指定しても無視され、
     * 認証済みユーザー自身が登録者になることを確認する（★AP06：なりすまし防止）。
     */
    public function test_store_ignores_user_id_in_request_body(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $response = $this->postJson(route('api.books.store'), [
            'user_id' => $otherUser->id,
            'title' => 'なりすまし試行book',
            'author' => '著者名',
            'isbn' => '5555555555555',
            'published_date' => '2020-01-01',
            'genre_id' => [$genre->id],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('books', [
            'isbn' => '5555555555555',
            'user_id' => $user->id,
        ]);
    }
}
