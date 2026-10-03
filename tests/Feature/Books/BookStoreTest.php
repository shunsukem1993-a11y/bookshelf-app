<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログイン済みユーザーが登録フォーム（全ジャンルのチェックボックス付き）を表示できることを確認する（C-11）。
     */
    public function test_authenticated_user_can_view_create_form_with_all_genres(): void
    {
        $user = User::factory()->create();
        Genre::factory()->create(['name' => 'エッセイ']);

        $response = $this->actingAs($user)->get(route('books.create'));

        $response->assertOk();
        $response->assertSee('エッセイ');
    }

    /**
     * 正常な入力で書籍を登録できることを確認する（C-12, C-13, C-14, C-15, C-16）。
     */
    public function test_user_can_store_a_book_with_valid_data(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => '新しい本',
            'author' => '著者名',
            'isbn' => '1234567890123',
            'published_date' => '2020-01-01',
            'description' => '説明文',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => $genres->pluck('id')->toArray(),
        ]);

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

        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '書籍を登録しました。');
    }

    /**
     * description・image_urlを省略しても登録できることを確認する（C-17）。
     */
    public function test_description_and_image_url_are_optional(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => '最小構成の本',
            'author' => '著者名',
            'isbn' => '1111111111111',
            'published_date' => '2020-01-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('books', [
            'isbn' => '1111111111111',
            'description' => null,
            'image_url' => null,
        ]);
    }
}
