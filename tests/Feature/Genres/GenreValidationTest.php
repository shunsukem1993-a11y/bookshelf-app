<?php

namespace Tests\Feature\Genres;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 無効な名前で登録すると、エラーが表示され、ジャンルが保存されないことを確認する。
     *
     * @dataProvider invalidNameProvider
     */
    public function test_store_rejects_invalid_name(mixed $name, string $message): void
    {
        Genre::factory()->create(['name' => '小説']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('genres.create'))
            ->post(route('genres.store'), ['name' => $name]);

        $response->assertSessionHasErrors(['name' => $message]);
        $this->assertDatabaseCount('genres', 1);
    }

    /**
     * 無効な名前で更新すると、エラーが表示され、ジャンルが変わらないことを確認する。
     *
     * @dataProvider invalidNameProvider
     */
    public function test_update_rejects_invalid_name(mixed $name, string $message): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);
        Genre::factory()->create(['name' => '技術書']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('genres.edit', $genre))
            ->put(route('genres.update', $genre), ['name' => $name]);

        $response->assertSessionHasErrors(['name' => $message]);
        $this->assertDatabaseHas('genres', ['id' => $genre->id, 'name' => '小説']);
    }

    /**
     * 他のジャンルと同じ名前で更新すると、一意性のエラーになることを確認する。
     */
    public function test_update_rejects_name_used_by_another_genre(): void
    {
        $genre = Genre::factory()->create(['name' => '小説']);
        Genre::factory()->create(['name' => '技術書']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('genres.edit', $genre))
            ->put(route('genres.update', $genre), ['name' => '技術書']);

        $response->assertSessionHasErrors(['name' => 'そのジャンル名はすでに登録されています。']);
    }

    /**
     * 登録で、既存のジャンル名と同じ名前は登録できないことを確認する。
     */
    public function test_store_rejects_duplicate_name(): void
    {
        Genre::factory()->create(['name' => '小説']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('genres.create'))
            ->post(route('genres.store'), ['name' => '小説']);

        $response->assertSessionHasErrors(['name' => 'そのジャンル名はすでに登録されています。']);
        $this->assertDatabaseCount('genres', 1);
    }

    /**
     * 無効な名前の一覧（入力値, メッセージ）。
     *
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function invalidNameProvider(): array
    {
        return [
            '名前が未入力' => ['', 'ジャンル名は必須です。'],
            '名前が256文字' => [str_repeat('あ', 256), 'ジャンル名は255文字以内で入力してください。'],
            '名前が配列' => [['小説'], 'ジャンル名は文字列で入力してください。'],
        ];
    }
}
