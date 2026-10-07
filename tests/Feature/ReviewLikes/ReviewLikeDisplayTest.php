<?php

namespace Tests\Feature\ReviewLikes;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * いいね件数が正しく表示されることを確認する。
     * 他のユーザーがいいねしていても、自分が未登録なら「いいね (n)」と表示され、
     * 件数には他のユーザーの分も含まれることを確認する。
     */
    public function test_shows_like_count_including_other_users_likes(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);
        $likers = User::factory()->count(3)->create();
        $review->likedByUsers()->attach($likers->pluck('id'));

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertSee('いいね (3)');
        $response->assertDontSee('いいね済み');
    }

    /**
     * いいね済みのユーザーには「いいね済み (n)」が表示されることを確認する。
     */
    public function test_shows_liked_state_for_user_who_liked(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);
        $user->likedReviews()->attach($review->id);

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertSee('いいね済み (1)');
    }

    /**
     * ゲストには、ログイン画面へのリンクが付いた状態で表示されることを確認する。
     */
    public function test_guest_sees_like_link_to_login(): void
    {
        $book = Book::factory()->create();
        Review::factory()->create(['book_id' => $book->id]);

        $response = $this->get(route('books.show', $book));

        $response->assertSee('href="'.route('login').'"', false);
        $response->assertSee('いいね (0)');
    }
}
