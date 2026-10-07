<?php

namespace Tests\Feature\Favorites;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteToggleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未登録の書籍をトグルすると、お気に入りに追加され、元の画面へリダイレクトされることを確認する。
     * 追加しても、他の書籍のお気に入りは変わらないことも確認する。
     */
    public function test_toggle_adds_unregistered_book_to_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $other = Book::factory()->create();
        $user->favoriteBooks()->attach($other->id);

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $other->id]);
        $response->assertRedirect(route('books.show', $book));
    }

    /**
     * 登録済みの書籍をトグルすると、お気に入りから解除されることを確認する。
     */
    public function test_toggle_removes_registered_book_from_favorites(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $user->favoriteBooks()->attach($book->id);

        $this->actingAs($user)->post(route('favorites.toggle', $book));

        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    /**
     * 同じ書籍を繰り返しトグルしても、重複した行が作られないことを確認する。
     */
    public function test_repeated_toggle_does_not_create_duplicate_rows(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)->post(route('favorites.toggle', $book));
        $this->actingAs($user)->post(route('favorites.toggle', $book));
        $this->actingAs($user)->post(route('favorites.toggle', $book));

        $this->assertDatabaseCount('favorites', 1);
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);
    }

    /**
     * 自分が登録した書籍もお気に入りに登録できることを確認する。
     */
    public function test_user_can_favorite_own_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post(route('favorites.toggle', $book));

        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'book_id' => $book->id]);
    }
}
