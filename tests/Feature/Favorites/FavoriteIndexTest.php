<?php

namespace Tests\Feature\Favorites;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みで一覧が表示され、ログインユーザーのお気に入りだけが表示されることを確認する。
     */
    public function test_index_shows_only_own_favorites(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $mine = Book::factory()->create(['title' => '自分のお気に入り']);
        $theirs = Book::factory()->create(['title' => '他人のお気に入り']);
        $user->favoriteBooks()->attach($mine->id);
        $other->favoriteBooks()->attach($theirs->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('自分のお気に入り');
        $response->assertDontSee('他人のお気に入り');
    }

    /**
     * 11冊のお気に入りは、1ページ目に10件、2ページ目に1件表示されることを確認する。
     */
    public function test_index_is_paginated_by_ten_per_page(): void
    {
        $user = User::factory()->create();
        $user->favoriteBooks()->attach(Book::factory()->count(11)->create()->pluck('id'));

        $page1 = $this->actingAs($user)->get(route('favorites.index'));
        $this->assertCount(10, $page1->viewData('books'));
        $this->assertSame(11, $page1->viewData('books')->total());

        $page2 = $this->actingAs($user)->get(route('favorites.index', ['page' => 2]));
        $this->assertCount(1, $page2->viewData('books'));
    }

    /**
     * 登録日時の新しい順に並ぶことを確認する（中間テーブルの created_at を明示して検証する）。
     */
    public function test_favorites_are_listed_newest_first(): void
    {
        $user = User::factory()->create();
        $older = Book::factory()->create(['title' => '先にお気に入りに入れた本']);
        $newer = Book::factory()->create(['title' => '後にお気に入りに入れた本']);
        $user->favoriteBooks()->attach([
            $older->id => ['created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2)],
            $newer->id => ['created_at' => now()->subDay(), 'updated_at' => now()->subDay()],
        ]);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertSeeInOrder(['後にお気に入りに入れた本', '先にお気に入りに入れた本']);
    }

    /**
     * 書籍タイトルが書籍詳細（books.show）へのリンクになっていることを確認する。
     */
    public function test_title_links_to_book_detail(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        $user->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertSee('href="'.route('books.show', $book).'"', false);
    }

    /**
     * 一覧からお気に入りを解除すると、リダイレクト後に一覧から消えることを確認する。
     */
    public function test_removing_from_index_hides_the_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['title' => '一覧から解除する本']);
        $user->favoriteBooks()->attach($book->id);

        $this->actingAs($user)
            ->from(route('favorites.index'))
            ->post(route('favorites.toggle', $book))
            ->assertRedirect(route('favorites.index'));

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertDontSee('一覧から解除する本');
    }

    /**
     * お気に入りが0件のとき、その旨のメッセージが表示されることを確認する。
     */
    public function test_shows_message_when_no_favorites(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('お気に入りに登録された書籍はありません。');
    }
}
