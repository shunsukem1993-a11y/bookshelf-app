<?php

namespace Tests\Unit\Services;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use App\Services\BookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * createWithGenres()が登録者を設定して書籍を作成し、指定したジャンルを紐づけることを確認する。
     */
    public function test_create_with_genres_creates_book_and_syncs_genres(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $book = app(BookService::class)->createWithGenres($user, [
            'title' => 'タイトル',
            'author' => '著者',
            'isbn' => '1234567890123',
            'published_date' => '2020-01-01',
            'description' => null,
            'image_url' => null,
            'genres' => $genres->pluck('id')->all(),
        ]);

        $this->assertSame($user->id, $book->user_id);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'タイトル']);
        $this->assertEqualsCanonicalizing(
            $genres->pluck('id')->all(),
            $book->genres()->pluck('genres.id')->all()
        );
    }

    /**
     * updateWithGenres()が書籍の属性を更新し、ジャンルの紐づけを新しい内容に
     * 同期（指定されなかった既存のジャンルは解除）することを確認する。
     */
    public function test_update_with_genres_updates_book_and_resyncs_genres(): void
    {
        $book = Book::factory()->create(['title' => '元のタイトル']);
        $oldGenre = Genre::factory()->create();
        $book->genres()->attach($oldGenre);
        $newGenre = Genre::factory()->create();

        $updated = app(BookService::class)->updateWithGenres($book, [
            'title' => '新しいタイトル',
            'author' => $book->author,
            'isbn' => $book->isbn,
            'published_date' => '2021-02-02',
            'description' => null,
            'image_url' => null,
            'genres' => [$newGenre->id],
        ]);

        $this->assertSame('新しいタイトル', $updated->title);
        $this->assertEqualsCanonicalizing(
            [$newGenre->id],
            $book->genres()->pluck('genres.id')->all()
        );
    }
}
