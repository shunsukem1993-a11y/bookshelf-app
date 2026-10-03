<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 投稿者本人がレビューを削除でき、書籍詳細へリダイレクトして成功メッセージが表示されることを確認する。
     */
    public function test_owner_can_delete_own_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $response = $this->actingAs($user)->delete(route('reviews.destroy', $review));

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを削除しました。');
    }

    /**
     * レビューを削除すると、そのレビューに付いたいいね（review_user）も削除されることを確認する。
     */
    public function test_deleting_review_cascades_likes(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);
        $liker = User::factory()->create();
        $review->likedByUsers()->attach($liker);

        $this->actingAs($user)->delete(route('reviews.destroy', $review));

        $this->assertDatabaseMissing('review_user', ['review_id' => $review->id]);
    }
}
