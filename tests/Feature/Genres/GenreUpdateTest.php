<?php

namespace Tests\Feature\Genres;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 編集画面に現在のジャンル名が初期値として表示されることを確認する。
     */
    public function test_edit_form_shows_current_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);

        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertOk();
        $response->assertSee('value="小説"', false);
    }

    /**
     * 名前を変更すると、ジャンルが更新され、一覧へリダイレクトされて成功メッセージが表示されることを確認する。
     */
    public function test_user_can_update_genre_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), ['name' => '長編小説']);

        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '長編小説']);
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを更新しました。');
    }

    /**
     * 自身と同じ名前で更新でき、一意性のエラーにならないことを確認する。
     */
    public function test_genre_can_keep_its_own_name(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);

        $response = $this->actingAs($user)->put(route('genres.update', $genre), ['name' => '小説']);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('genres.index'));
    }

    /**
     * 失敗時は編集画面へ戻り、ジャンルが変わらないことを確認する。
     */
    public function test_failed_update_returns_to_edit_form_without_changing_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);

        $response = $this->actingAs($user)
            ->from(route('genres.edit', $genre))
            ->put(route('genres.update', $genre), ['name' => '']);

        $response->assertRedirect(route('genres.edit', $genre));
        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '小説']);
    }
}
