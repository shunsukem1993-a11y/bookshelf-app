<?php

namespace Tests\Feature\Genres;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストが一覧・登録画面・編集画面を開くと、ログイン画面へリダイレクトされることを確認する。
     */
    public function test_guest_is_redirected_from_index_create_and_edit_pages(): void
    {
        $genre = Genre::factory()->create();

        $this->get(route('genres.index'))->assertRedirect(route('login'));
        $this->get(route('genres.create'))->assertRedirect(route('login'));
        $this->get(route('genres.edit', $genre))->assertRedirect(route('login'));
    }

    /**
     * ゲストが詳細を開くと、ログイン画面へリダイレクトされることを確認する。
     */
    public function test_guest_is_redirected_from_detail_page(): void
    {
        $genre = Genre::factory()->create();

        $this->get(route('genres.show', $genre))->assertRedirect(route('login'));
    }

    /**
     * ゲストが登録・更新・削除を行うと、ログイン画面へリダイレクトされ、データが変わらないことを確認する。
     */
    public function test_guest_cannot_store_update_or_destroy_genres(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);
        $book = Book::factory()->create();
        $linked = Genre::factory()->create(['name' => '技術書']);
        $linked->books()->attach($book->id);

        $this->post(route('genres.store'), ['name' => '旅行'])->assertRedirect(route('login'));
        $this->put(route('genres.update', $genre), ['name' => '別名'])->assertRedirect(route('login'));
        $this->delete(route('genres.destroy', $genre))->assertRedirect(route('login'));

        $this->assertDatabaseCount('genres', 2);
        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '小説']);
    }

    /**
     * 認証済みで存在しないジャンルの詳細・編集・更新・削除は、404になることを確認する。
     */
    public function test_nonexistent_genre_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/genres/999999')->assertNotFound();
        $this->actingAs($user)->get('/genres/999999/edit')->assertNotFound();
        $this->actingAs($user)->put('/genres/999999', ['name' => '存在しない'])->assertNotFound();
        $this->actingAs($user)->delete('/genres/999999')->assertNotFound();
    }
}
