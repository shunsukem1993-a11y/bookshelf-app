<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 他ユーザーが編集画面にアクセスすると403になることを確認する。
     */
    public function test_other_user_cannot_access_edit_page(): void
    {
        [$owner, $otherUser, $review] = $this->createOwnedReview();

        $response = $this->actingAs($otherUser)->get(route('reviews.edit', $review));

        $response->assertForbidden();
    }

    /**
     * 他ユーザーが更新しようとしても、入力内容（有効・無効）に関係なく403になり、DBが変わらないことを確認する。
     *
     * 認可はバリデーションより先に行われるため、無効な値でもバリデーションエラー（302）にはならない。
     *
     * @dataProvider otherUserUpdatePayloadProvider
     *
     * @param  array<string, mixed>  $payload
     */
    public function test_other_user_cannot_update_review(array $payload): void
    {
        [$owner, $otherUser, $review] = $this->createOwnedReview();

        $response = $this->actingAs($otherUser)->put(route('reviews.update', $review), $payload);

        $response->assertForbidden();
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);
    }

    /**
     * 他ユーザーが削除しようとすると403になり、DBから削除されないことを確認する。
     */
    public function test_other_user_cannot_delete_review(): void
    {
        [$owner, $otherUser, $review] = $this->createOwnedReview();

        $response = $this->actingAs($otherUser)->delete(route('reviews.destroy', $review));

        $response->assertForbidden();
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    /**
     * ゲストが投稿しようとすると、ログイン画面へリダイレクトされ、保存されないことを確認する。
     */
    public function test_guest_cannot_store_review(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('reviews.store', $book), [
            'rating' => 3,
            'comment' => 'ゲストの投稿です。',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * ゲストが編集画面にアクセスすると、ログイン画面へリダイレクトされることを確認する。
     */
    public function test_guest_is_redirected_from_edit_page(): void
    {
        [$owner, $guest, $review] = $this->createOwnedReview();

        $response = $this->get(route('reviews.edit', $review));

        $response->assertRedirect(route('login'));
    }

    /**
     * ゲストが更新しようとすると、ログイン画面へリダイレクトされ、DBが変わらないことを確認する。
     */
    public function test_guest_cannot_update_review(): void
    {
        [$owner, $guest, $review] = $this->createOwnedReview();

        $response = $this->put(route('reviews.update', $review), [
            'rating' => 1,
            'comment' => 'ゲストによる更新です。',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);
    }

    /**
     * ゲストが削除しようとすると、ログイン画面へリダイレクトされ、DBから削除されないことを確認する。
     */
    public function test_guest_cannot_delete_review(): void
    {
        [$owner, $guest, $review] = $this->createOwnedReview();

        $response = $this->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    /**
     * 認証済みでも、存在しない書籍への投稿は404になることを確認する。
     */
    public function test_store_returns_404_for_nonexistent_book(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/books/999999/reviews', [
            'rating' => 3,
            'comment' => '存在しない書籍への投稿です。',
        ]);

        $response->assertNotFound();
        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * 認証済みでも、存在しないレビューの編集画面は404になることを確認する。
     */
    public function test_edit_returns_404_for_nonexistent_review(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/reviews/999999/edit');

        $response->assertNotFound();
    }

    /**
     * 認証済みでも、存在しないレビューの更新は404になることを確認する。
     */
    public function test_update_returns_404_for_nonexistent_review(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/reviews/999999', [
            'rating' => 3,
            'comment' => '存在しないレビューです。',
        ]);

        $response->assertNotFound();
    }

    /**
     * 認証済みでも、存在しないレビューの削除は404になることを確認する。
     */
    public function test_destroy_returns_404_for_nonexistent_review(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/reviews/999999');

        $response->assertNotFound();
    }

    /**
     * 他ユーザーの更新で使う入力値の一覧（有効な値・無効な値）。
     *
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function otherUserUpdatePayloadProvider(): array
    {
        return [
            '有効な値' => [['rating' => 1, 'comment' => '他人による更新です。']],
            '無効な値' => [['rating' => 9, 'comment' => '']],
        ];
    }

    /**
     * 投稿者（所有者）・他ユーザー・レビューを用意する。
     *
     * @return array{0: User, 1: User, 2: Review}
     */
    private function createOwnedReview(): array
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);

        return [$owner, $otherUser, $review];
    }
}
