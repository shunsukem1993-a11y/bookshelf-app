<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Reviewモデルのテスト用Factory。
 *
 * 注意: このFactoryはモデル単体テスト（tests/Unit/Models配下）でのみ使用する。
 * Seeder（ReviewSeeder）では仕様書記載のレビューデータをcreateで直接投入するため、
 * このFactoryは使用しない。
 *
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
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
            'book_id' => Book::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->sentence(),
        ];
    }
}
