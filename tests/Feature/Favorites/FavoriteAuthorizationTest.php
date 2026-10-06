<?php

namespace Tests\Feature\Favorites;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストがトグルと一覧にアクセスすると、ログイン画面へリダイレクトされ、お気に入りは変わらないことを確認する。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $book = Book::factory()->create();

        $this->post(route('favorites.toggle', $book))->assertRedirect(route('login'));
        $this->get(route('favorites.index'))->assertRedirect(route('login'));

        $this->assertDatabaseCount('book_user', 0);
    }

    /**
     * 認証済みで、存在しない書籍へのトグルは404になることを確認する。
     */
    public function test_toggle_returns_404_for_nonexistent_book(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/books/999999/favorites')->assertNotFound();
    }
}
