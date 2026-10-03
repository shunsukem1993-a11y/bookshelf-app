<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍登録（POST /books）のバリデーションを検証する。
     *
     * @dataProvider commonValidationCasesProvider
     */
    public function test_store_validates_common_rules(string $field, mixed $invalidValue, string $errorKey, string $expectedMessage): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload[$field] = $invalidValue;

        $response = $this->actingAs($user)->post(route('books.store'), $payload);

        $response->assertSessionHasErrors([$errorKey => $expectedMessage]);
        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 書籍更新（PUT /books/{book}）のバリデーションを検証する（E-16）。
     *
     * @dataProvider commonValidationCasesProvider
     */
    public function test_update_validates_common_rules(string $field, mixed $invalidValue, string $errorKey, string $expectedMessage): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload[$field] = $invalidValue;

        $response = $this->actingAs($owner)->put(route('books.update', $book), $payload);

        $response->assertSessionHasErrors([$errorKey => $expectedMessage]);
    }

    /**
     * 登録・更新で共通のバリデーションケース（必須・境界値・形式）を提供する（E-01〜E-13, B-01〜B-14）。
     *
     * @return array<string, array{0: string, 1: mixed, 2: string, 3: string}>
     */
    public static function commonValidationCasesProvider(): array
    {
        return [
            // E-01, E-02, E-06, E-09, E-11
            'title required (E-01)' => ['title', '', 'title', 'タイトルは必須です。'],
            'author required (E-02)' => ['author', '', 'author', '著者名は必須です。'],
            'published_date required (E-06)' => ['published_date', '', 'published_date', '出版日は必須です。'],
            'published_date invalid format (E-07)' => ['published_date', '2024/01/01', 'published_date', '出版日はYYYY-MM-DD形式で入力してください。'],
            'image_url invalid format (E-09)' => ['image_url', 'not-a-url', 'image_url', '画像URLはURL形式で入力してください。'],
            'genres empty array (E-11)' => ['genres', [], 'genres', 'ジャンルを1つ以上選択してください。'],
            'genres nonexistent id (E-12)' => ['genres', [999999], 'genres.0', '指定されたジャンルは存在しません。'],
            'genres non-integer (E-13)' => ['genres', ['abc'], 'genres.0', 'ジャンルIDは整数で指定してください。'],

            // 境界値（B-01〜B-14）
            'title 256 chars (B-02)' => ['title', str_repeat('あ', 256), 'title', 'タイトルは255文字以内で入力してください。'],
            'author 256 chars (B-04)' => ['author', str_repeat('い', 256), 'author', '著者名は255文字以内で入力してください。'],
            'isbn 12 digits (B-05)' => ['isbn', '123456789012', 'isbn', 'ISBNは13桁で入力してください。'],
            'isbn 14 digits (B-07)' => ['isbn', '12345678901234', 'isbn', 'ISBNは13桁で入力してください。'],
            'description 256 chars (B-09)' => ['description', str_repeat('う', 256), 'description', '説明は255文字以内で入力してください。'],
            'image_url 256 chars (B-12)' => ['image_url', 'https://example.com/'.str_repeat('a', 240), 'image_url', '画像URLは255文字以内で入力してください。'],
            'genres 0 items (B-13)' => ['genres', [], 'genres', 'ジャンルを1つ以上選択してください。'],
        ];
    }

    /**
     * isbn必須（E-03）を検証する（存在チェックの都合でproviderと分離）。
     */
    public function test_store_requires_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['isbn'] = '';

        $response = $this->actingAs($user)->post(route('books.store'), $payload);

        $response->assertSessionHasErrors(['isbn' => 'ISBNは必須です。']);
    }

    /**
     * isbnに数字以外が含まれる場合のエラーを検証する（E-04）。
     */
    public function test_store_rejects_non_digit_isbn(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['isbn'] = '123456789012A';

        $response = $this->actingAs($user)->post(route('books.store'), $payload);

        $response->assertSessionHasErrors(['isbn' => 'ISBNは13桁で入力してください。']);
    }

    /**
     * isbnが既存書籍と重複する場合のエラーを検証する（E-05）。
     */
    public function test_store_rejects_duplicate_isbn(): void
    {
        $user = User::factory()->create();
        $existing = Book::factory()->create(['isbn' => '1234567890123']);
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['isbn'] = $existing->isbn;

        $response = $this->actingAs($user)->post(route('books.store'), $payload);

        $response->assertSessionHasErrors(['isbn' => '入力されたISBNはすでに登録されています。']);
    }

    /**
     * title/authorがちょうど255文字の場合は成功することを確認する（B-01, B-03）。
     */
    public function test_store_accepts_title_and_author_at_255_chars(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['title'] = str_repeat('あ', 255);
        $payload['author'] = str_repeat('い', 255);

        $response = $this->actingAs($user)->post(route('books.store'), $payload);

        $response->assertSessionDoesntHaveErrors();
    }

    /**
     * isbnがちょうど13桁の場合は成功することを確認する（B-06）。
     */
    public function test_store_accepts_isbn_at_13_digits(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['isbn'] = '1234567890123';

        $response = $this->actingAs($user)->post(route('books.store'), $payload);

        $response->assertSessionDoesntHaveErrors();
    }

    /**
     * description/image_urlがちょうど255文字の場合は成功することを確認する（B-08, B-11）。
     */
    public function test_store_accepts_description_and_image_url_at_255_chars(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['description'] = str_repeat('う', 255);
        $payload['image_url'] = 'https://example.com/'.str_repeat('a', 255 - strlen('https://example.com/'));

        $response = $this->actingAs($user)->post(route('books.store'), $payload);

        $response->assertSessionDoesntHaveErrors();
    }

    /**
     * genresがちょうど1件の場合は成功することを確認する（B-14）。
     *
     * B-10（description/image_url未入力で成功）はBookStoreTest::test_description_and_image_url_are_optionalで検証済み。
     */
    public function test_store_accepts_single_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);

        $response = $this->actingAs($user)->post(route('books.store'), $payload);

        $response->assertSessionDoesntHaveErrors();
    }

    /**
     * バリデーション失敗時、入力値がold()で保持されることを確認する（E-14）。
     */
    public function test_store_keeps_old_input_on_validation_failure(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['title'] = '';
        $payload['author'] = '保持されるべき著者名';

        $response = $this->actingAs($user)->post(route('books.store'), $payload);

        $response->assertSessionHasInput('author', '保持されるべき著者名');
    }

    /**
     * バリデーション失敗時、/books/createにリダイレクトされ書籍が作成されないことを確認する（E-15）。
     */
    public function test_store_does_not_create_book_on_validation_failure(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['title'] = '';

        $response = $this->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.store'), $payload);

        $response->assertRedirect(route('books.create'));
        $this->assertDatabaseCount('books', 0);
    }

    /**
     * 更新時、他の書籍のISBNに変更しようとすると一意性エラーになることを確認する（E-17）。
     */
    public function test_update_rejects_isbn_duplicated_with_another_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id, 'isbn' => '1111111111111']);
        $otherBook = Book::factory()->create(['isbn' => '2222222222222']);
        $genre = Genre::factory()->create();

        $payload = $this->validPayload($genre->id);
        $payload['isbn'] = $otherBook->isbn;

        $response = $this->actingAs($owner)->put(route('books.update', $book), $payload);

        $response->assertSessionHasErrors(['isbn' => '入力されたISBNはすでに登録されています。']);
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
            'genres' => [$genreId],
        ];
    }
}
