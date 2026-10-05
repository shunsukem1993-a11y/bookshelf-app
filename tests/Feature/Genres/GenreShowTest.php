<?php

namespace Tests\Feature\Genres;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 見出しにジャンル名が表示されることを確認する。
     */
    public function test_shows_genre_name_in_heading(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create(['name' => '小説']);

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('ジャンル: 小説');
    }

    /**
     * ジャンルに紐づく書籍だけが表示され、紐づかない書籍は表示されないことを確認する。
     */
    public function test_shows_only_books_linked_to_the_genre(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $linked = Book::factory()->create(['title' => '吾輩は猫である']);
        $unlinked = Book::factory()->create(['title' => '別ジャンルの本']);
        $genre->books()->attach($linked->id);

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertSee('吾輩は猫である');
        $response->assertDontSee('別ジャンルの本');
    }

    /**
     * 書籍は10件/ページでページネーションされ、11件の場合は2ページ目に1件表示されることを確認する。
     */
    public function test_books_are_paginated_by_ten_per_page(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $genre->books()->attach(Book::factory()->count(11)->create()->pluck('id'));

        $page1 = $this->actingAs($user)->get(route('genres.show', $genre));
        $this->assertCount(10, $page1->viewData('books'));
        $this->assertSame(11, $page1->viewData('books')->total());

        $page2 = $this->actingAs($user)->get(route('genres.show', [$genre, 'page' => 2]));
        $this->assertCount(1, $page2->viewData('books'));
    }

    /**
     * 書籍は登録順（books.id昇順）で並ぶことを確認する。
     */
    public function test_books_are_listed_in_registration_order(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $first = Book::factory()->create(['title' => '先に登録した本']);
        $second = Book::factory()->create(['title' => '後に登録した本']);
        $genre->books()->attach([$second->id, $first->id]);

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertSeeInOrder(['先に登録した本', '後に登録した本']);
    }

    /**
     * 紐づく書籍が0件のとき、その旨のメッセージが表示されることを確認する。
     */
    public function test_shows_message_when_genre_has_no_books(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('このジャンルの書籍はまだ登録されていません。');
    }
}
