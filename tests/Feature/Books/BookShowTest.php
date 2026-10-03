<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストが書籍詳細を表示でき、基本情報・ジャンルが表示されることを確認する（C-07）。
     */
    public function test_guest_can_view_book_detail(): void
    {
        $book = Book::factory()->create([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'isbn' => '9784101010014',
            'published_date' => '1905-01-01',
            'description' => '猫目線の物語。',
            'image_url' => 'https://example.com/cat.jpg',
        ]);
        $genre = Genre::factory()->create(['name' => '小説']);
        $book->genres()->attach($genre);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('吾輩は猫である');
        $response->assertSee('夏目漱石');
        $response->assertSee('9784101010014');
        $response->assertSee('1905-01-01');
        $response->assertSee('猫目線の物語。');
        $response->assertSee('小説');
    }

    /**
     * レビュー一覧（投稿者名・コメント）が表示されることを確認する（C-08）。
     */
    public function test_book_detail_shows_review_list(): void
    {
        $book = Book::factory()->create();
        $reviewer = User::factory()->create(['name' => '山田太郎']);
        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => '最高でした。',
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertSee('山田太郎');
        $response->assertSee('最高でした。');
    }

    /**
     * レビューのいいね数が表示されることを確認する（C-09）。
     */
    public function test_review_like_count_is_displayed(): void
    {
        $book = Book::factory()->create();
        $review = Review::factory()->create(['book_id' => $book->id]);
        $likers = User::factory()->count(3)->create();
        $review->likedByUsers()->attach($likers->pluck('id'));

        $response = $this->get(route('books.show', $book));

        $response->assertSee('いいね (3)');
    }

    /**
     * descriptionがnullの場合、説明セクションが表示されずエラーにならないことを確認する（C-10）。
     */
    public function test_book_without_description_hides_description_section(): void
    {
        $book = Book::factory()->create(['description' => null]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertDontSee('説明:');
    }

    /**
     * レビューの評価（★）と投稿日（Y/m/d）が表示されることを確認する。
     */
    public function test_review_shows_rating_stars_and_posted_date(): void
    {
        $book = Book::factory()->create();
        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 3,
            'created_at' => Carbon::create(2026, 1, 15, 10, 0, 0),
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertSee('★★★☆☆');
        $response->assertSee('2026/01/15');
    }

    /**
     * レビューが投稿日時の新しい順に表示されることを確認する。
     */
    public function test_reviews_are_shown_newest_first(): void
    {
        $book = Book::factory()->create();
        Review::factory()->create([
            'book_id' => $book->id,
            'comment' => '古いレビュー',
            'created_at' => Carbon::create(2026, 1, 1, 10, 0, 0),
        ]);
        Review::factory()->create([
            'book_id' => $book->id,
            'comment' => '新しいレビュー',
            'created_at' => Carbon::create(2026, 1, 10, 10, 0, 0),
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertSeeInOrder(['新しいレビュー', '古いレビュー']);
    }
}
