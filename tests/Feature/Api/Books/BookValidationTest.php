<?php

namespace Tests\Feature\Api\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍登録（POST /api/v1/books）の共通バリデーションを検証する。
     *
     * @dataProvider commonValidationCasesProvider
     */
    public function test_store_validates_common_rules(string $field, mixed $invalidValue, string $errorKey, string $expectedMessage): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload[$field] = $invalidValue;

        $response = $this->postJson(route('api.books.store'), $payload);

        $response->assertStatus(422);
        $this->assertSame($expectedMessage, $response->json('errors')[$errorKey][0] ?? null);
        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 書籍更新（PUT /api/v1/books/{book}）の共通バリデーションを検証する。
     *
     * @dataProvider commonValidationCasesProvider
     */
    public function test_update_validates_common_rules(string $field, mixed $invalidValue, string $errorKey, string $expectedMessage): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        Sanctum::actingAs($owner);
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['isbn'] = $book->isbn;
        $payload[$field] = $invalidValue;

        $response = $this->putJson(route('api.books.update', $book), $payload);

        $response->assertStatus(422);
        $this->assertSame($expectedMessage, $response->json('errors')[$errorKey][0] ?? null);
    }

    /**
     * 登録・更新で共通のバリデーションケース（必須・境界値・形式）を提供する（API-29〜API-38）。
     *
     * @return array<string, array{0: string, 1: mixed, 2: string, 3: string}>
     */
    public static function commonValidationCasesProvider(): array
    {
        return [
            // API-29: title
            'title required (API-29)' => ['title', '', 'title', 'タイトルは必須です。'],
            'title 256 chars (API-29)' => ['title', str_repeat('あ', 256), 'title', 'タイトルは255文字以内で入力してください。'],

            // API-30: author
            'author required (API-30)' => ['author', '', 'author', '著者名は必須です。'],
            'author 256 chars (API-30)' => ['author', str_repeat('い', 256), 'author', '著者名は255文字以内で入力してください。'],

            // API-33: published_date
            'published_date invalid format (API-33)' => ['published_date', '2024/01/01', 'published_date', '出版日はYYYY-MM-DD形式で入力してください。'],

            // API-34: description
            'description 256 chars (API-34)' => ['description', str_repeat('う', 256), 'description', '説明は255文字以内で入力してください。'],

            // API-35: image_url
            'image_url invalid format (API-35)' => ['image_url', 'not-a-url', 'image_url', '画像URLはURL形式で入力してください。'],
            'image_url 256 chars (API-35)' => ['image_url', 'https://example.com/'.str_repeat('a', 240), 'image_url', '画像URLは255文字以内で入力してください。'],

            // API-36〜API-38: genre_id
            'genre_id empty array (API-36)' => ['genre_id', [], 'genre_id', 'ジャンルを1つ以上選択してください。'],
            'genre_id nonexistent id (API-37)' => ['genre_id', [999999], 'genre_id.0', '指定されたジャンルは存在しません。'],
            'genre_id non-integer (API-38)' => ['genre_id', ['abc'], 'genre_id.0', 'ジャンルIDは整数で指定してください。'],
        ];
    }

    /**
     * isbn必須（API-31）を検証する（存在チェックの都合でproviderと分離）。
     */
    public function test_store_requires_isbn(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['isbn'] = '';

        $response = $this->postJson(route('api.books.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.isbn.0', 'ISBNは必須です。');
    }

    /**
     * isbnが13桁以外の場合のエラーを検証する（API-31）。
     */
    public function test_store_rejects_isbn_not_13_digits(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['isbn'] = '123456789012';

        $response = $this->postJson(route('api.books.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.isbn.0', 'ISBNは13桁で入力してください。');
    }

    /**
     * isbnが既存書籍と重複する場合のエラーを検証する（API-31）。
     */
    public function test_store_rejects_duplicate_isbn(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $existing = Book::factory()->create(['isbn' => '1234567890123']);
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['isbn'] = $existing->isbn;

        $response = $this->postJson(route('api.books.store'), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.isbn.0', '入力されたISBNはすでに登録されています。');
    }

    /**
     * published_dateを省略しても登録できることを確認する（★DR01: nullable化）。
     */
    public function test_store_accepts_missing_published_date(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        unset($payload['published_date']);

        $response = $this->postJson(route('api.books.store'), $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('books', [
            'title' => $payload['title'],
            'published_date' => null,
        ]);
    }

    /**
     * 更新時、他の書籍のISBNに変更しようとすると一意性エラーになることを確認する（API-32）。
     */
    public function test_update_rejects_isbn_duplicated_with_another_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id, 'isbn' => '1111111111111']);
        Sanctum::actingAs($owner);
        $otherBook = Book::factory()->create(['isbn' => '2222222222222']);
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['isbn'] = $otherBook->isbn;

        $response = $this->putJson(route('api.books.update', $book), $payload);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.isbn.0', '入力されたISBNはすでに登録されています。');
    }

    /**
     * 登録用の有効なリクエストボディを生成する。
     *
     * @return array<string, mixed>
     */
    private function validPayload(int $genreId): array
    {
        return [
            'title' => '有効なタイトル',
            'author' => '有効な著者',
            'isbn' => (string) fake()->unique()->numerify('#############'),
            'published_date' => '2020-01-01',
            'description' => '説明文',
            'image_url' => 'https://example.com/image.jpg',
            'genre_id' => [$genreId],
        ];
    }
}
