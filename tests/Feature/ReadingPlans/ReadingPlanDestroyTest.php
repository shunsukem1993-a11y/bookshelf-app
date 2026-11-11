<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanDestroyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 所有者が読書計画を削除できることを確認する（issue #67）。
     */
    public function test_owner_can_delete_reading_plan(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseMissing('reading_plans', ['id' => $readingPlan->id]);
    }

    /**
     * 完了済みの計画も削除できることを確認する（削除は状態を問わない。issue #67）。
     */
    public function test_owner_can_delete_completed_reading_plan(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));
        $this->assertDatabaseMissing('reading_plans', ['id' => $readingPlan->id]);
    }

    /**
     * 他ユーザーが削除しようとすると403になり、DBから削除されないことを確認する（issue #67、鈴木花子シナリオ相当）。
     */
    public function test_other_user_cannot_delete(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($otherUser)->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertForbidden();
        $this->assertDatabaseHas('reading_plans', ['id' => $readingPlan->id]);
    }

    /**
     * 未認証でアクセスすると/loginにリダイレクトされることを確認する（issue #67）。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $response = $this->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('login'));
    }
}
