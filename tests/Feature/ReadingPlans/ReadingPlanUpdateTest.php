<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 所有者が編集フォームを表示でき、既存の期日が初期値になることを確認する（issue #69）。
     */
    public function test_owner_can_view_edit_form_with_existing_data(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => '2027-01-01',
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $readingPlan));

        $response->assertOk();
        $response->assertSee('2027-01-01');
    }

    /**
     * 所有者が期日を更新できることを確認する（issue #69）。
     */
    public function test_owner_can_update_target_date(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['user_id' => $user->id, 'target_date' => '2027-01-01']);

        $response = $this->actingAs($user)->put(route('reading-plans.update', $readingPlan), [
            'target_date' => '2027-02-02',
        ]);

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertSame('2027-02-02', $readingPlan->refresh()->target_date->toDateString());
    }

    /**
     * target_dateが必須であることを確認する（issue #69）。
     */
    public function test_target_date_is_required(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put(route('reading-plans.update', $readingPlan), [
            'target_date' => '',
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日は必須です。']);
    }

    /**
     * target_dateの形式が不正な場合にエラーになることを確認する（issue #69）。
     */
    public function test_target_date_must_be_valid_format(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put(route('reading-plans.update', $readingPlan), [
            'target_date' => '2027/02/02',
        ]);

        $response->assertSessionHasErrors(['target_date' => '期日はYYYY-MM-DD形式で入力してください。']);
    }

    /**
     * 他ユーザーが編集フォームにアクセスすると403になることを確認する（issue #69、鈴木花子シナリオ相当）。
     */
    public function test_other_user_cannot_access_edit_page(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->get(route('reading-plans.edit', $readingPlan));

        $response->assertForbidden();
    }

    /**
     * 他ユーザーが更新しようとすると、入力内容に関係なく403になり、DBが更新されないことを確認する（issue #69）。
     */
    public function test_other_user_cannot_update(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['user_id' => $owner->id, 'target_date' => '2027-01-01']);

        $response = $this->actingAs($otherUser)->put(route('reading-plans.update', $readingPlan), [
            'target_date' => '2027-02-02',
        ]);

        $response->assertForbidden();
        $this->assertSame('2027-01-01', $readingPlan->refresh()->target_date->toDateString());
    }

    /**
     * 完了済みの計画は編集できない（403）ことを確認する（issue #69、編集制限の条件）。
     */
    public function test_completed_plan_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
            'target_date' => '2027-01-01',
        ]);

        $editResponse = $this->actingAs($user)->get(route('reading-plans.edit', $readingPlan));
        $updateResponse = $this->actingAs($user)->put(route('reading-plans.update', $readingPlan), [
            'target_date' => '2027-02-02',
        ]);

        $editResponse->assertForbidden();
        $updateResponse->assertForbidden();
    }

    /**
     * 未認証でアクセスすると/loginにリダイレクトされることを確認する（issue #69）。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $response = $this->put(route('reading-plans.update', $readingPlan), [
            'target_date' => '2027-02-02',
        ]);

        $response->assertRedirect(route('login'));
    }
}
