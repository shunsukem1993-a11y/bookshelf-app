<?php

namespace Tests\Feature\ReviewLikes;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストがトグルすると、ログイン画面へリダイレクトされ、データが変わらないことを確認する。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $review = Review::factory()->create();

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('review_likes', 0);
    }

    /**
     * 認証済みで、存在しないレビューへのトグルは404になることを確認する。
     */
    public function test_toggle_returns_404_for_nonexistent_review(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/reviews/999999/like')->assertNotFound();
    }
}
