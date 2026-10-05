<?php

namespace Tests\Feature\Genres;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 書籍が紐づかないジャンルは削除され、一覧へリダイレクトされて成功メッセージが表示されることを確認する。
     * 削除しても、他のジャンルと書籍は変わらないことも確認する。
     */
    public function test_genre_without_books_is_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '旅行']);
        $other = Genre::factory()->create(['name' => '小説']);
        $book = Book::factory()->create();
        $other->books()->attach($book->id);

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
        $this->assertDatabaseHas('genres', ['id' => $other->id]);
        $this->assertDatabaseHas('books', ['id' => $book->id]);
        $this->assertDatabaseHas('book_genre', ['genre_id' => $other->id, 'book_id' => $book->id]);
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを削除しました。');
    }

    /**
     * 書籍が紐づくジャンルは削除されず、紐づきも残り、制限のメッセージが表示されることを確認する。
     */
    public function test_genre_with_books_is_not_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);
        $book = Book::factory()->create();
        $genre->books()->attach($book->id);

        $response = $this->actingAs($user)->delete(route('genres.destroy', $genre));

        $this->assertDatabaseHas('genres', ['id' => $genre->id]);
        $this->assertDatabaseHas('book_genre', ['genre_id' => $genre->id, 'book_id' => $book->id]);
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('error', 'このジャンルには書籍が紐づいているため削除できません。');
    }
}
