<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 無効な値で投稿すると、書籍詳細へ戻り、エラーになり、保存されないことを確認する。
     *
     * @dataProvider invalidReviewPayloadProvider
     *
     * @param  array<string, mixed>  $payload
     */
    public function test_store_rejects_invalid_payload(array $payload, string $field): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), $payload);

        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHasErrors($field);
        $this->assertDatabaseCount('reviews', 0);
    }

    /**
     * 無効な値で更新すると、編集画面へ戻り、エラーになり、DBは変わらないことを確認する。
     *
     * @dataProvider invalidReviewPayloadProvider
     *
     * @param  array<string, mixed>  $payload
     */
    public function test_update_rejects_invalid_payload_and_returns_to_edit_page(array $payload, string $field): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);

        $response = $this->actingAs($user)
            ->from(route('reviews.edit', $review))
            ->put(route('reviews.update', $review), $payload);

        $response->assertRedirect(route('reviews.edit', $review));
        $response->assertSessionHasErrors($field);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '元のコメント',
        ]);
    }

    /**
     * 境界値の入力で投稿すると、エラーなく保存されることを確認する。
     *
     * @dataProvider validReviewBoundaryProvider
     *
     * @param  array<string, mixed>  $payload
     */
    public function test_store_accepts_boundary_values(array $payload): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.store', $book), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => $payload['rating'],
            'comment' => $payload['comment'],
        ]);
    }

    /**
     * 境界値の入力で更新すると、エラーなく更新されることを確認する。
     *
     * @dataProvider validReviewBoundaryProvider
     *
     * @param  array<string, mixed>  $payload
     */
    public function test_update_accepts_boundary_values(array $payload): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $response = $this->actingAs($user)
            ->from(route('reviews.edit', $review))
            ->put(route('reviews.update', $review), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => $payload['rating'],
            'comment' => $payload['comment'],
        ]);
    }

    /**
     * 無効な入力の一覧（説明 => [入力値, エラーになる項目]）。
     *
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidReviewPayloadProvider(): array
    {
        return [
            'rating 未入力' => [['comment' => 'コメント'], 'rating'],
            'rating が 0' => [['rating' => 0, 'comment' => 'コメント'], 'rating'],
            'rating が 6' => [['rating' => 6, 'comment' => 'コメント'], 'rating'],
            'rating が文字列' => [['rating' => 'abc', 'comment' => 'コメント'], 'rating'],
            'rating が小数' => [['rating' => 3.5, 'comment' => 'コメント'], 'rating'],
            'comment 未入力' => [['rating' => 3], 'comment'],
            'comment が空白のみ' => [['rating' => 3, 'comment' => '   '], 'comment'],
            'comment が256文字' => [['rating' => 3, 'comment' => str_repeat('a', 256)], 'comment'],
            'comment が日本語256文字' => [['rating' => 3, 'comment' => str_repeat('あ', 256)], 'comment'],
        ];
    }

    /**
     * 境界値の有効な入力の一覧。
     *
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function validReviewBoundaryProvider(): array
    {
        return [
            'rating が 1' => [['rating' => 1, 'comment' => 'コメント']],
            'rating が 5' => [['rating' => 5, 'comment' => 'コメント']],
            'comment が255文字' => [['rating' => 3, 'comment' => str_repeat('a', 255)]],
            'comment が日本語255文字' => [['rating' => 3, 'comment' => str_repeat('あ', 255)]],
        ];
    }
}
