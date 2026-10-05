<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Genre N : N Book（book_genre）を確認する。
     */
    public function test_genre_belongs_to_many_books(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $genre->books()->attach($book->id);

        $this->assertCount(1, $genre->books);
        $this->assertEquals($book->id, $genre->books->first()->id);
    }

    /**
     * 書籍が紐づいたジャンルは、DBの外部キー制約（RESTRICT）により削除できず、ジャンルと紐づきが残ることを確認する。
     */
    public function test_genre_with_books_cannot_be_deleted_by_database(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();
        $genre->books()->attach($book->id);

        try {
            $genre->delete();
            $this->fail('書籍が紐づいたジャンルが削除されてしまいました。');
        } catch (QueryException) {
            $this->assertDatabaseHas('genres', ['id' => $genre->id]);
            $this->assertDatabaseHas('book_genre', ['genre_id' => $genre->id, 'book_id' => $book->id]);
        }
    }
}
