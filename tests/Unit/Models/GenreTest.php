<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
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
}
