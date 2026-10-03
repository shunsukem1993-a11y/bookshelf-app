<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * レビューが0件の場合、「まだレビューはありません。」が表示されることを確認する。
     */
    public function test_shows_empty_message_when_no_reviews(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertSee('まだレビューはありません。');
    }

    /**
     * 未投稿のログインユーザーには、投稿フォームが表示されることを確認する。
     */
    public function test_shows_form_to_user_without_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertSee('name="rating"', false);
    }

    /**
     * 投稿済みのログインユーザーには、フォームが表示されず、投稿済みの文言が表示されることを確認する。
     */
    public function test_hides_form_and_shows_message_to_user_with_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        Review::factory()->create(['user_id' => $user->id, 'book_id' => $book->id]);

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertDontSee('name="rating"', false);
        $response->assertSee('この書籍には既にレビューを投稿しています。');
    }

    /**
     * ゲストには、フォームの代わりにログインへの案内が表示されることを確認する。
     */
    public function test_guest_sees_login_prompt_instead_of_form(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertDontSee('name="rating"', false);
        $response->assertSee('レビューを投稿するには');
    }

    /**
     * 投稿者本人には「編集」「削除」が表示され、他人には表示されないことを確認する。
     */
    public function test_only_owner_sees_edit_and_delete_buttons(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create(['user_id' => $owner->id, 'book_id' => $book->id]);

        // いいねフォームのURL（/reviews/{id}/like）と区別するため、属性の全体で判定する
        $editLink = 'href="'.route('reviews.edit', $review).'"';
        $destroyForm = 'action="'.route('reviews.destroy', $review).'"';

        $ownerResponse = $this->actingAs($owner)->get(route('books.show', $book));
        $ownerResponse->assertSee($editLink, false);
        $ownerResponse->assertSee($destroyForm, false);

        $otherResponse = $this->actingAs($otherUser)->get(route('books.show', $book));
        $otherResponse->assertDontSee($editLink, false);
        $otherResponse->assertDontSee($destroyForm, false);
    }
}
