<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Bookモデルのテスト用Factory。
 *
 * 注意: このFactoryはモデル単体テスト（tests/Unit/Models配下）でのみ使用する。
 * Seeder（BookSeeder）では仕様書記載の固定書籍データをfirstOrCreateで直接投入するため、
 * このFactoryは使用しない。
 *
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'isbn' => fake()->unique()->ean13(),
            'published_at' => fake()->date(),
            'description' => fake()->sentence(),
            'image_url' => fake()->imageUrl(),
        ];
    }
}
