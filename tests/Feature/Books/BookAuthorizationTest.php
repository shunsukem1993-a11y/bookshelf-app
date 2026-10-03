<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 他ユーザーが編集画面にアクセスすると403になることを確認する（A-02）。
     *
     * A-01（所有者が編集画面を表示できる）はBookUpdateTest::test_owner_can_view_edit_form_with_existing_dataで検証済み。
     */
    public function test_other_user_cannot_access_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->get(route('books.edit', $book));

        $response->assertForbidden();
    }

    /**
     * 他ユーザーが正しい入力で更新しようとしても403になり、DBが更新されないことを確認する（A-04）。
     *
     * 注意: ここでは必ず有効なリクエストボディを使う。UpdateBookRequestのバリデーションは
     * コントローラの$this->authorize()より先に実行されるため、無効な入力だと
     * バリデーションエラー（302）が返り、認可（403）を検証できないため。
     *
     * A-03（所有者が更新できる）はBookUpdateTest::test_owner_can_update_book_with_valid_dataで検証済み。
     */
    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id, 'title' => '元のタイトル']);
        $genre = Genre::factory()->create();

        $response = $this->actingAs($otherUser)->put(route('books.update', $book), $this->validPayload($book, $genre));

        $response->assertForbidden();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '元のタイトル']);
    }

    /**
     * 他ユーザーが削除しようとすると403になり、DBから削除されないことを確認する（A-06）。
     *
     * A-05（所有者が削除できる）はBookDestroyTest::test_owner_can_delete_own_bookで検証済み。
     */
    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->delete(route('books.destroy', $book));

        $response->assertForbidden();
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    /**
     * ログイン済みユーザーが存在しない書籍IDで編集画面にアクセスすると404になることを確認する（E-19）。
     */
    public function test_edit_returns_404_for_nonexistent_book(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/books/999999/edit');

        $response->assertNotFound();
    }

    /**
     * 存在しない書籍IDを更新しようとすると404になることを確認する（E-20）。
     */
    public function test_update_returns_404_for_nonexistent_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->put('/books/999999', [
            'title' => '有効なタイトル',
            'author' => '有効な著者',
            'isbn' => '1234567890123',
            'published_date' => '2020-01-01',
            'genres' => [$genre->id],
        ]);

        $response->assertNotFound();
    }

    /**
     * 存在しない書籍IDを削除しようとすると404になることを確認する（E-21）。
     */
    public function test_destroy_returns_404_for_nonexistent_book(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/books/999999');

        $response->assertNotFound();
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
            'genres' => [$genre->id],
        ];
    }
}
