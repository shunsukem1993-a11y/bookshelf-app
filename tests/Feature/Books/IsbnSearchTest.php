<?php

namespace Tests\Feature\Books;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証の場合は401（JSONリクエストのため）になることを確認する（issue #60）。
     */
    public function test_guest_cannot_search_by_isbn(): void
    {
        $response = $this->getJson(route('books.isbn-search', ['isbn' => '9784101010014']));

        $response->assertStatus(401);
    }

    /**
     * 正常なISBNでGoogle Books APIのレスポンスが正しく整形されて返ることを確認する（issue #60）。
     */
    public function test_valid_isbn_returns_book_information(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'items' => [[
                    'volumeInfo' => [
                        'title' => '吾輩は猫である',
                        'authors' => ['夏目漱石'],
                        'description' => '猫の視点から人間社会を風刺的に描いた物語。',
                        'imageLinks' => ['thumbnail' => 'http://books.google.com/thumb.jpg'],
                        'publishedDate' => '1905-01-01',
                    ],
                ]],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson(route('books.isbn-search', ['isbn' => '9784101010014']));

        $response->assertOk();
        $response->assertExactJson([
            'title' => '吾輩は猫である',
            'author' => '夏目漱石',
            'description' => '猫の視点から人間社会を風刺的に描いた物語。',
            'image_url' => 'http://books.google.com/thumb.jpg',
            'published_date' => '1905-01-01',
        ]);
        Http::assertSent(fn ($request) => str_contains(urldecode($request->url()), 'isbn:9784101010014'));
    }

    /**
     * APIキーが設定されている場合、リクエストにkeyパラメータが付与されることを確認する。
     */
    public function test_request_includes_api_key_when_configured(): void
    {
        config(['services.google_books.key' => 'dummy-api-key']);

        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'items' => [['volumeInfo' => ['title' => '吾輩は猫である']]],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson(route('books.isbn-search', ['isbn' => '9784101010014']));

        $response->assertOk();
        Http::assertSent(fn ($request) => $request['key'] === 'dummy-api-key');
    }

    /**
     * APIキーが未設定の場合、リクエストにkeyパラメータが付与されないことを確認する。
     */
    public function test_request_omits_api_key_when_not_configured(): void
    {
        config(['services.google_books.key' => null]);

        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'items' => [['volumeInfo' => ['title' => '吾輩は猫である']]],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson(route('books.isbn-search', ['isbn' => '9784101010014']));

        $response->assertOk();
        Http::assertSent(fn ($request) => ! isset($request['key']));
    }

    /**
     * 複数著者の場合、「、」区切りで連結されることを確認する（issue #60）。
     */
    public function test_multiple_authors_are_joined_with_japanese_comma(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'items' => [['volumeInfo' => ['title' => '共著本', 'authors' => ['著者A', '著者B']]]],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson(route('books.isbn-search', ['isbn' => '1111111111111']));

        $response->assertJsonPath('author', '著者A、著者B');
    }

    /**
     * 13桁以外のISBNは422になり、外部APIが呼ばれないことを確認する（issue #60）。
     */
    public function test_isbn_not_13_digits_returns_422_without_calling_api(): void
    {
        $user = User::factory()->create();
        Http::fake();

        $response = $this->actingAs($user)->getJson(route('books.isbn-search', ['isbn' => '12345']));

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'ISBNは13桁の数字で指定してください。');
        Http::assertNothingSent();
    }

    /**
     * 該当書籍が見つからない場合は404になることを確認する（issue #60）。
     */
    public function test_isbn_not_found_returns_404(): void
    {
        $user = User::factory()->create();

        Http::fake(['www.googleapis.com/*' => Http::response(['totalItems' => 0], 200)]);

        $response = $this->actingAs($user)->getJson(route('books.isbn-search', ['isbn' => '9999999999999']));

        $response->assertStatus(404);
        $response->assertJsonPath('error', '該当する書籍情報が見つかりませんでした。');
    }

    /**
     * 外部APIがエラーレスポンスを返した場合は502になることを確認する（issue #60）。
     */
    public function test_external_api_error_returns_502(): void
    {
        $user = User::factory()->create();

        Http::fake(['www.googleapis.com/*' => Http::response('Server Error', 500)]);

        $response = $this->actingAs($user)->getJson(route('books.isbn-search', ['isbn' => '9999999999998']));

        $response->assertStatus(502);
        $response->assertJsonPath('error', '書籍情報の取得に失敗しました。時間をおいて再度お試しください。');
    }

    /**
     * 外部APIへの接続自体が失敗した場合も502になることを確認する（issue #60）。
     */
    public function test_external_api_connection_failure_returns_502(): void
    {
        $user = User::factory()->create();

        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        $response = $this->actingAs($user)->getJson(route('books.isbn-search', ['isbn' => '9999999999997']));

        $response->assertStatus(502);
    }
}
