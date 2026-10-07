<?php

namespace Tests\Feature\ReviewLikes;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeToggleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未登録のレビューをトグルすると、いいねが追加され、元の画面へリダイレクトされることを確認する。
     * 追加しても、他のレビューのいいねは変わらないことも確認する。
     */
    public function test_toggle_adds_like_to_review_and_redirects_back(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);
        $otherReview = Review::factory()->create(['book_id' => $book->id]);
        $user->likedReviews()->attach($otherReview->id);

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        $this->assertDatabaseHas('review_likes', ['user_id' => $user->id, 'review_id' => $review->id]);
        $this->assertDatabaseHas('review_likes', ['user_id' => $user->id, 'review_id' => $otherReview->id]);
        $response->assertRedirect(route('books.show', $book));
    }

    /**
     * 登録済みのレビューをトグルすると、いいねが解除されることを確認する。
     */
    public function test_toggle_removes_existing_like(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();
        $user->likedReviews()->attach($review->id);

        $this->actingAs($user)->post(route('reviews.like', $review));

        $this->assertDatabaseMissing('review_likes', ['user_id' => $user->id, 'review_id' => $review->id]);
    }

    /**
     * 同じレビューを繰り返しトグルしても、重複した行が作られないことを確認する。
     */
    public function test_repeated_toggle_does_not_create_duplicate_rows(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($user)->post(route('reviews.like', $review));
        $this->actingAs($user)->post(route('reviews.like', $review));
        $this->actingAs($user)->post(route('reviews.like', $review));

        $this->assertDatabaseCount('review_likes', 1);
        $this->assertDatabaseHas('review_likes', ['user_id' => $user->id, 'review_id' => $review->id]);
    }

    /**
     * 自分のレビューにもいいねできることを確認する。
     */
    public function test_user_can_like_own_review(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('reviews.like', $review));

        $this->assertDatabaseHas('review_likes', ['user_id' => $user->id, 'review_id' => $review->id]);
    }
}
