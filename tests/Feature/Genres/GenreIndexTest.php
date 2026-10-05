<?php

namespace Tests\Feature\Genres;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みで一覧が表示され、全ジャンル名が表示されることを確認する。
     */
    public function test_user_can_view_genre_index(): void
    {
        $user = User::factory()->create();
        Genre::factory()->create(['name' => '小説']);
        Genre::factory()->create(['name' => '技術書']);

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertOk();
        $response->assertSee('小説');
        $response->assertSee('技術書');
    }

    /**
     * 各ジャンルの書籍数（books_count）が正しく付与されることを確認する。
     */
    public function test_each_genre_shows_its_book_count(): void
    {
        $user = User::factory()->create();
        $novel = Genre::factory()->create(['name' => '小説']);
        $tech = Genre::factory()->create(['name' => '技術書']);
        Genre::factory()->create(['name' => '旅行']);

        $novel->books()->attach(Book::factory()->count(2)->create()->pluck('id'));
        $tech->books()->attach(Book::factory()->create()->id);

        $genres = $this->actingAs($user)
            ->get(route('genres.index'))
            ->viewData('genres');

        $this->assertSame(2, $genres->firstWhere('name', '小説')->books_count);
        $this->assertSame(1, $genres->firstWhere('name', '技術書')->books_count);
        $this->assertSame(0, $genres->firstWhere('name', '旅行')->books_count);
    }

    /**
     * ジャンルが登録順（id昇順）で並ぶことを確認する。
     */
    public function test_genres_are_listed_in_registration_order(): void
    {
        $user = User::factory()->create();
        Genre::factory()->create(['name' => '歴史']);
        Genre::factory()->create(['name' => '科学']);
        Genre::factory()->create(['name' => '芸術']);

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertSeeInOrder(['歴史', '科学', '芸術']);
    }

    /**
     * 各ジャンル名が詳細画面（/genres/{genre}）へのリンクになっていることを確認する。
     */
    public function test_genre_name_links_to_detail_page(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertSee('href="'.route('genres.show', $genre).'"', false);
    }

    /**
     * 各ジャンルに、編集と削除の操作が表示されることを確認する。
     */
    public function test_each_genre_shows_edit_and_delete_actions(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertSee('href="'.route('genres.edit', $genre).'"', false);
        $response->assertSee('action="'.route('genres.destroy', $genre).'"', false);
    }

    /**
     * ジャンルが0件のとき、「ジャンルが登録されていません。」が表示されることを確認する。
     */
    public function test_shows_empty_message_when_no_genres(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertOk();
        $response->assertSee('ジャンルが登録されていません。');
    }
}
