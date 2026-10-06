<?php

namespace Tests\Feature\Favorites;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 登録済みのユーザーには「お気に入りから削除」のボタンが表示されることを確認する。
     */
    public function test_registered_user_sees_remove_button(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $user->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('title="お気に入りから削除"', false);
    }

    /**
     * 未登録のユーザーには「お気に入りに追加」のボタンが表示され、「お気に入りから削除」は表示されないことを確認する。
     * 他のユーザーがお気に入りに登録していても、表示に影響しないことも確認する。
     */
    public function test_unregistered_user_sees_add_button(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $book = Book::factory()->create();
        $other->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('title="お気に入りに追加"', false);
        $response->assertDontSee('title="お気に入りから削除"', false);
    }

    /**
     * ゲストには、ログインへの案内が表示されることを確認する。
     */
    public function test_guest_sees_login_prompt(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('お気に入りに追加するにはログインしてください');
    }
}
