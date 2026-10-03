<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 有効な値で投稿すると、レビューが保存され、書籍詳細へリダイレクトして成功メッセージが表示されることを確認する。
     */
    public function test_user_can_store_review_with_valid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 4,
            'comment' => '読みやすかったです。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
            'comment' => '読みやすかったです。',
        ]);
        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'レビューを投稿しました。');
    }

    /**
     * 自分が登録した書籍にも投稿できることを確認する。
     */
    public function test_owner_can_review_own_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => '自分の本です。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    /**
     * 投稿済みのユーザーが再投稿すると、保存されずエラーになることを確認する。
     */
    public function test_duplicate_review_is_rejected(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), [
                'rating' => 3,
                'comment' => '二回目の投稿です。',
            ]);

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('error', 'この書籍には既にレビューを投稿済みです。');
        $this->assertDatabaseCount('reviews', 1);
    }

    /**
     * 別のユーザーは、同じ書籍に投稿できることを確認する。
     */
    public function test_other_user_can_review_same_book(): void
    {
        $book = Book::factory()->create();
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        Review::factory()->create(['user_id' => $firstUser->id, 'book_id' => $book->id]);

        $this->actingAs($secondUser)->post(route('reviews.store', $book), [
            'rating' => 3,
            'comment' => '別のユーザーの投稿です。',
        ]);

        $this->assertDatabaseCount('reviews', 2);
        $this->assertDatabaseHas('reviews', ['user_id' => $secondUser->id, 'book_id' => $book->id]);
    }

    /**
     * 同じユーザーは、別の書籍には投稿できることを確認する。
     */
    public function test_same_user_can_review_different_book(): void
    {
        $user = User::factory()->create();
        $firstBook = Book::factory()->create();
        $secondBook = Book::factory()->create();
        Review::factory()->create(['user_id' => $user->id, 'book_id' => $firstBook->id]);

        $this->actingAs($user)->post(route('reviews.store', $secondBook), [
            'rating' => 3,
            'comment' => '別の書籍への投稿です。',
        ]);

        $this->assertDatabaseCount('reviews', 2);
        $this->assertDatabaseHas('reviews', ['user_id' => $user->id, 'book_id' => $secondBook->id]);
    }
}
