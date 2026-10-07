<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Book N : 1 User（登録者）を確認する。
     */
    public function test_book_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $book->user);
        $this->assertEquals($user->id, $book->user->id);
    }

    /**
     * Book N : N Genre（book_genre）を確認する。
     */
    public function test_book_belongs_to_many_genres(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $this->assertCount(1, $book->genres);
        $this->assertEquals($genre->id, $book->genres->first()->id);
    }

    /**
     * genres()にwithTimestamps()が設定されており、
     * book_genreのcreated_at / updated_atが記録されることを確認する。
     */
    public function test_genres_pivot_has_timestamps(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $pivot = $book->genres->first()->pivot;
        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    /**
     * Book 1 : N Review（この書籍へのレビュー）を確認する。
     */
    public function test_book_has_many_reviews(): void
    {
        $book = Book::factory()->create();
        $review1 = Review::factory()->create(['book_id' => $book->id]);
        $review2 = Review::factory()->create(['book_id' => $book->id]);

        $this->assertCount(2, $book->reviews);
        $this->assertTrue($book->reviews->contains($review1));
        $this->assertTrue($book->reviews->contains($review2));
    }

    /**
     * Book N : N User（favorites：お気に入りしたユーザー）を確認する。
     */
    public function test_book_belongs_to_many_favorited_by_users(): void
    {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $book->favoritedByUsers()->attach($user->id);

        $this->assertCount(1, $book->favoritedByUsers);
        $this->assertEquals($user->id, $book->favoritedByUsers->first()->id);
    }
}
