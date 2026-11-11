<?php

namespace Tests\Feature\ReadingPlans;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証でアクセスすると/loginにリダイレクトされることを確認する（issue #67）。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertRedirect(route('login'));
    }

    /**
     * ログインユーザー自身の読書計画のみが表示されることを確認する（issue #67）。
     */
    public function test_index_shows_only_own_reading_plans(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownPlan = ReadingPlan::factory()->create(['user_id' => $user->id]);
        ReadingPlan::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertOk();
        $this->assertSame([$ownPlan->id], $response->viewData('readingPlans')->pluck('id')->toArray());
    }

    /**
     * 状態（status）で絞り込みできることを確認する（issue #67）。
     */
    public function test_index_filters_by_status(): void
    {
        $user = User::factory()->create();
        $inProgress = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
        ]);
        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.index', ['status' => ReadingPlanStatus::InProgress->value]));

        $this->assertSame([$inProgress->id], $response->viewData('readingPlans')->pluck('id')->toArray());
    }
}
