<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 作成者本人が編集フォームを表示でき、既存データが初期値表示されることを確認する（C-18）。
     */
    public function test_owner_can_view_edit_form_with_existing_data(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id, 'title' => '元のタイトル']);
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);

        $response = $this->actingAs($owner)->get(route('books.edit', $book));

        $response->assertOk();
        $response->assertSee('元のタイトル');
        $response->assertSee('checked', false);
    }

    /**
     * 作成者本人が正常な入力で書籍を更新できることを確認する（C-19, C-21, C-22）。
     */
    public function test_owner_can_update_book_with_valid_data(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $genre = Genre::factory()->create();

        $response = $this->actingAs($owner)->put(route('books.update', $book), [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'published_date' => '2021-02-02',
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', '書籍を更新しました。');
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
        ]);
    }

    /**
     * ジャンルの選択を変更した場合、book_genreが同期（追加・解除）されることを確認する（C-20）。
     */
    public function test_updating_genres_syncs_pivot_table(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);
        $oldGenre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();
        $book->genres()->attach($oldGenre);

        $this->actingAs($owner)->put(route('books.update', $book), $this->validPayload($book, [
            'genres' => [$newGenre->id],
        ]));

        $book->refresh();
        $this->assertCount(1, $book->genres);
        $this->assertSame($newGenre->id, $book->genres->first()->id);
    }

    /**
     * 自身のISBNを変更せずに更新しても一意性エラーにならないことを確認する（C-23）。
     */
    public function test_owner_can_keep_own_isbn_unchanged(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id, 'isbn' => '2222222222222']);

        $response = $this->actingAs($owner)->put(
            route('books.update', $book),
            $this->validPayload($book, ['isbn' => '2222222222222'])
        );

        $response->assertSessionDoesntHaveErrors();
    }

    /**
     * ISBNを別の未使用の値に変更できることを確認する（C-24）。
     */
    public function test_owner_can_change_isbn_to_an_unused_value(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id, 'isbn' => '3333333333333']);

        $response = $this->actingAs($owner)->put(
            route('books.update', $book),
            $this->validPayload($book, ['isbn' => '4444444444444'])
        );

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('books', ['id' => $book->id, 'isbn' => '4444444444444']);
    }

    /**
     * 更新用の有効なリクエストボディを生成する。
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Book $book, array $overrides = []): array
    {
        return array_merge([
            'title' => '有効なタイトル',
            'author' => '有効な著者',
            'isbn' => $book->isbn,
            'published_date' => '2020-01-01',
            'genres' => [Genre::factory()->create()->id],
        ], $overrides);
    }
}
