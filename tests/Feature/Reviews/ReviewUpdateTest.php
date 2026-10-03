<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 投稿者本人が編集画面を開くと、既存の書籍名とコメントが表示されることを確認する。
     */
    public function test_owner_can_view_edit_form_with_existing_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['title' => '吾輩は猫である']);
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'comment' => '元のコメントです。',
        ]);

        $response = $this->actingAs($user)->get(route('reviews.edit', $review));

        $response->assertOk();
        $response->assertSee('吾輩は猫である');
        $response->assertSee('元のコメントです。');
    }

    /**
     * 投稿者本人が有効な値で更新すると、レビューが変わり、書籍詳細へリダイレクトして成功メッセージが表示されることを確認する。
     */
    public function test_owner_can_update_review_with_valid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);

        $response = $this->actingAs($user)->put(route('reviews.update', $review), [
            'rating' => 5,
            'comment' => '更新後のコメント',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '更新後のコメント',
        ]);
        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを更新しました。');
    }
}
