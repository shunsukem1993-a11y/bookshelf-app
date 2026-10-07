<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Review N : 1 User（投稿者）を確認する。
     */
    public function test_review_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $review->user);
        $this->assertEquals($user->id, $review->user->id);
    }

    /**
     * Review N : 1 Book（対象書籍）を確認する。
     */
    public function test_review_belongs_to_book(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);

        $this->assertInstanceOf(Book::class, $review->book);
        $this->assertEquals($book->id, $review->book->id);
    }

    /**
     * Review N : N User（review_likes：いいねしたユーザー）を確認する。
     */
    public function test_review_belongs_to_many_liked_by_users(): void
    {
        $review = Review::factory()->create();
        $user = User::factory()->create();

        $review->likedByUsers()->attach($user->id);

        $this->assertCount(1, $review->likedByUsers);
        $this->assertEquals($user->id, $review->likedByUsers->first()->id);
    }

    /**
     * 同じユーザーが同じ書籍に重複してレビューすると、UNIQUE制約により例外になることを確認する。
     */
    public function test_duplicate_user_and_book_review_violates_unique_constraint(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $this->expectException(UniqueConstraintViolationException::class);

        Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);
    }
}
