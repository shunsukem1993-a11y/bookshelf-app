<?php

namespace Tests\Feature\Genres;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みで登録画面が表示され、フォームの送信先が登録ルートであることを確認する。
     */
    public function test_user_can_view_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.create'));

        $response->assertOk();
        $response->assertSee(route('genres.store'), false);
    }

    /**
     * 有効な値で登録すると、ジャンルが保存され、一覧へリダイレクトされて成功メッセージが表示されることを確認する。
     */
    public function test_user_can_store_genre_with_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('genres.store'), ['name' => 'ミステリー']);

        $this->assertDatabaseHas('genres', ['name' => 'ミステリー']);
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンルを登録しました。');
    }

    /**
     * 失敗時は登録画面へ戻り、ジャンルが保存されないことを確認する。
     */
    public function test_failed_store_returns_to_form_without_saving(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('genres.create'))
            ->post(route('genres.store'), ['name' => '']);

        $response->assertRedirect(route('genres.create'));
        $this->assertDatabaseCount('genres', 0);
    }

    /**
     * 名前が255文字のジャンルは登録できることを確認する（境界値）。
     */
    public function test_store_accepts_name_of_255_characters(): void
    {
        $user = User::factory()->create();
        $name = str_repeat('あ', 255);

        $this->actingAs($user)->post(route('genres.store'), ['name' => $name]);

        $this->assertDatabaseHas('genres', ['name' => $name]);
    }
}
