<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 作成者本人が書籍を削除できることを確認する（C-25, C-26, C-27）。
     */
    public function test_owner_can_delete_own_book(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($owner)->delete(route('books.destroy', $book));

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '書籍を削除しました。');
    }

    /**
     * 削除時に関連データ（ジャンル紐付け・お気に入り・レビュー・いいね）が
     * カスケードで適切に処理されることを確認する（C-28, C-29）。
     */
    public function test_deleting_book_cascades_related_data(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id]);

        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);

        $favoriteUser = User::factory()->create();
        $book->favoritedByUsers()->attach($favoriteUser);

        $review = Review::factory()->create(['book_id' => $book->id]);
        $liker = User::factory()->create();
        $review->likedByUsers()->attach($liker);

        $this->actingAs($owner)->delete(route('books.destroy', $book));

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('book_user', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('reviews', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('review_user', ['review_id' => $review->id]);
    }
}
