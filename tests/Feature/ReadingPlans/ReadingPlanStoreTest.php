<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 認証済みユーザーが作成フォームを表示できることを確認する（issue #67）。
     */
    public function test_authenticated_user_can_view_create_form(): void
    {
        $user = User::factory()->create();
        Book::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('reading-plans.create'));

        $response->assertOk();
        $this->assertCount(3, $response->viewData('books'));
    }

    /**
     * 未認証で作成フォームにアクセスすると/loginにリダイレクトされることを確認する（issue #67）。
     */
    public function test_guest_cannot_view_create_form(): void
    {
        $response = $this->get(route('reading-plans.create'));

        $response->assertRedirect(route('login'));
    }

    /**
     * 有効なデータで読書計画を登録でき、ステータスが既定でin_progressになることを確認する（issue #67）。
     */
    public function test_user_can_store_a_reading_plan_with_valid_data(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '2027-01-01',
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $plan = $user->readingPlans()->first();
        $this->assertNotNull($plan);
        $this->assertSame($book->id, $plan->book_id);
        $this->assertSame('2027-01-01', $plan->target_date->toDateString());
        $this->assertSame(ReadingPlanStatus::InProgress, $plan->status);
    }

    /**
     * 未認証で登録しようとすると/loginにリダイレクトされることを確認する（issue #67）。
     */
    public function test_guest_cannot_store_reading_plan(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '2027-01-01',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('reading_plans', 0);
    }

    /**
     * book_idが必須であることを確認する（issue #67）。
     */
    public function test_book_id_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'target_date' => '2027-01-01',
        ]);

        $response->assertSessionHasErrors(['book_id' => '書籍を選択してください。']);
    }

    /**
     * book_idが存在しない書籍の場合にエラーになることを確認する（issue #67）。
     */
    public function test_book_id_must_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => 999999,
            'target_date' => '2027-01-01',
        ]);

        $response->assertSessionHasErrors(['book_id' => '指定された書籍は存在しません。']);
    }

    /**
     * target_dateが必須であることを確認する（issue #67）。
     */
    public function test_target_date_is_required(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '',
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日は必須です。']);
    }

    /**
     * target_dateの形式が不正な場合にエラーになることを確認する（issue #67）。
     */
    public function test_target_date_must_be_valid_format(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '2027/01/01',
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日はYYYY-MM-DD形式で入力してください。']);
    }

    /**
     * 同一ユーザーが同じ書籍に対してin_progressの計画を重複登録できないことを確認する（issue #67）。
     */
    public function test_store_rejects_duplicate_in_progress_plan_for_same_book(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '2027-02-01',
        ]);

        $response->assertSessionHasErrors(['book_id' => 'この書籍は既に進行中の読書計画があります。']);
        $this->assertSame(1, ReadingPlan::where('book_id', $book->id)->count());
    }

    /**
     * 完了済みの計画がある書籍には、新たな計画を登録できる（読み直し）ことを確認する（issue #67）。
     */
    public function test_store_allows_new_plan_for_book_with_completed_plan(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '2027-03-01',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertSame(2, ReadingPlan::where('book_id', $book->id)->count());
    }
}
