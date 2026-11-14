<?php

namespace Tests\Feature\Api\Books;

use App\Http\Controllers\Api\BookController;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Tests\TestCase;

/**
 * 書籍登録・更新API（Api\BookController）のDB::transactionによる
 * ロールバック動作を検証するテスト。
 *
 * Web版（tests/Feature/Books/BookTransactionRollbackTest.php）と同じ理由で、
 * HTTP経由ではなくFormRequestを手動でバリデーション通過させたうえで
 * コントローラーを直接呼び出す。バリデーション通過後（＝対象ジャンルが
 * 実在すると確認した直後）にそのジャンルを削除することで、「検証時には
 * 存在したが更新処理の瞬間には存在しない」という競合状態を再現し、
 * 外部キー制約違反によるQueryExceptionを発生させる。
 *
 * API版は`auth:sanctum`ミドルウェアでユーザーを解決するが、ここでは
 * コントローラーを直接呼び出すためミドルウェアを経由しない。
 * setUserResolver()で直接ユーザーを注入するため、Sanctumへの依存はない。
 */
class BookTransactionRollbackTest extends TestCase
{
    use RefreshDatabase;

    /**
     * バリデーション通過後に対象ジャンルが削除され、genres()->sync()が
     * 外部キー制約違反で失敗した場合、書籍本体の登録もロールバックされることを確認する。
     */
    public function test_store_rolls_back_book_creation_when_genre_sync_fails(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $formRequest = StoreBookRequest::create('/v1/books', 'POST', [
            'title' => 'ロールバック検証用の本',
            'author' => '著者',
            'isbn' => '1234567890123',
            'published_date' => '2020-01-01',
            'genre_id' => [$genre->id],
        ]);
        $formRequest->setContainer($this->app);
        $formRequest->setUserResolver(fn () => $user);
        $formRequest->validateResolved();

        // バリデーション通過後に対象ジャンルが削除される競合状態を再現する
        $genre->delete();

        try {
            app(BookController::class)->store($formRequest);
            $this->fail('QueryExceptionが発生することを期待していましたが、発生しませんでした。');
        } catch (QueryException $e) {
            // 期待どおりの例外。ロールバックの検証に進む。
        }

        $this->assertDatabaseMissing('books', ['title' => 'ロールバック検証用の本']);
    }

    /**
     * バリデーション通過後に対象ジャンルが削除され、genres()->sync()が
     * 外部キー制約違反で失敗した場合、書籍の更新とジャンルの紐付けが
     * ともに更新前の状態に戻ることを確認する。
     */
    public function test_update_rolls_back_book_and_genres_when_genre_sync_fails(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $owner->id, 'title' => '元のタイトル']);
        $originalGenre = Genre::factory()->create();
        $book->genres()->attach($originalGenre);
        $doomedGenre = Genre::factory()->create();

        $formRequest = UpdateBookRequest::create("/v1/books/{$book->id}", 'PUT', [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'published_date' => '2021-02-02',
            'genre_id' => [$doomedGenre->id],
        ]);
        $formRequest->setContainer($this->app);
        $formRequest->setUserResolver(fn () => $owner);

        // authorize()がroute('book')を参照するため、ルートに対象Bookを注入する
        $route = new Route('PUT', '/v1/books/{book}', []);
        $route->bind($formRequest);
        $route->setParameter('book', $book);
        $formRequest->setRouteResolver(fn () => $route);

        $formRequest->validateResolved();

        // バリデーション通過後に更新先のジャンルが削除される競合状態を再現する
        $doomedGenre->delete();

        try {
            app(BookController::class)->update($formRequest, $book);
            $this->fail('QueryExceptionが発生することを期待していましたが、発生しませんでした。');
        } catch (QueryException $e) {
            // 期待どおりの例外。ロールバックの検証に進む。
        }

        $book->refresh()->load('genres');
        $this->assertSame('元のタイトル', $book->title);
        $this->assertEqualsCanonicalizing([$originalGenre->id], $book->genres->pluck('id')->toArray());
    }
}
