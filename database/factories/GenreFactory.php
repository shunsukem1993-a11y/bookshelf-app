<?php

namespace Database\Factories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Genreモデルのテスト用Factory。
 *
 * 注意: このFactoryはモデル単体テスト（tests/Unit/Models配下）でのみ使用する。
 * Seeder（GenreSeeder）では仕様書記載の固定ジャンル名をfirstOrCreateで直接投入するため、
 * このFactoryは使用しない。
 *
 * @extends Factory<Genre>
 */
class GenreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
        ];
    }
}
