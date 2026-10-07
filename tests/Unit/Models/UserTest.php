<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * User 1 : N Book（登録した書籍）を確認する。
     */
    public function test_user_has_many_books(): void
    {
        $user = User::factory()->create();
        $book1 = Book::factory()->create(['user_id' => $user->id]);
        $book2 = Book::factory()->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->books);
        $this->assertTrue($user->books->contains($book1));
        $this->assertTrue($user->books->contains($book2));
    }

    /**
     * User N : N Book（favorites：お気に入り）を確認する。
     */
    public function test_user_belongs_to_many_favorite_books(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $this->assertCount(1, $user->favoriteBooks);
        $this->assertEquals($book->id, $user->favoriteBooks->first()->id);
    }

    /**
     * favoriteBooks()にwithTimestamps()が設定されており、
     * favoritesのcreated_at / updated_atが記録されることを確認する。
     */
    public function test_favorite_books_pivot_has_timestamps(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $pivot = $user->favoriteBooks->first()->pivot;
        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    /**
     * User 1 : N Review（投稿したレビュー）を確認する。
     */
    public function test_user_has_many_reviews(): void
    {
        $user = User::factory()->create();
        $review1 = Review::factory()->create(['user_id' => $user->id]);
        $review2 = Review::factory()->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->reviews);
        $this->assertTrue($user->reviews->contains($review1));
        $this->assertTrue($user->reviews->contains($review2));
    }

    /**
     * User N : N Review（review_likes：いいね）を確認する。
     */
    public function test_user_belongs_to_many_liked_reviews(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $user->likedReviews()->attach($review->id);

        $this->assertCount(1, $user->likedReviews);
        $this->assertEquals($review->id, $user->likedReviews->first()->id);
    }

    /**
     * likedReviews()にwithTimestamps()が設定されており、
     * review_likesのcreated_at / updated_atが記録されることを確認する。
     */
    public function test_liked_reviews_pivot_has_timestamps(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $user->likedReviews()->attach($review->id);

        $pivot = $user->likedReviews->first()->pivot;
        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }
}
