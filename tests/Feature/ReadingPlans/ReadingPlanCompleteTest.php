<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanCompleteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 所有者が読書計画を読了済みにできることを確認する（issue #67）。
     */
    public function test_owner_can_complete_reading_plan(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));
        $readingPlan->refresh();
        $this->assertSame(ReadingPlanStatus::Completed, $readingPlan->status);
        $this->assertNotNull($readingPlan->completed_at);
    }

    /**
     * 期限切れの計画も読了済みにできることを確認する（issue #67）。
     */
    public function test_overdue_reading_plan_can_be_completed(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Overdue,
        ]);

        $response = $this->actingAs($user)->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertSame(ReadingPlanStatus::Completed, $readingPlan->refresh()->status);
    }

    /**
     * 既に完了済みの計画を再度読了しようとすると403になることを確認する（issue #67）。
     */
    public function test_cannot_complete_already_completed_plan(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();
    }

    /**
     * 他ユーザーが読了させようとすると403になり、状態が変わらないことを確認する（issue #67、鈴木花子シナリオ相当）。
     */
    public function test_other_user_cannot_complete(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($otherUser)->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();
        $this->assertSame(ReadingPlanStatus::InProgress, $readingPlan->refresh()->status);
    }

    /**
     * 未認証でアクセスすると/loginにリダイレクトされることを確認する（issue #67）。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $response = $this->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('login'));
    }
}
