<?php

namespace Tests\Feature\Console;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendReadingPlanRemindersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 期日を過ぎた進行中の計画が「期限切れ」に変更されることを確認する（issue #73）。
     */
    public function test_marks_overdue_in_progress_plans_as_overdue(): void
    {
        $plan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => today()->subDays(1),
        ]);

        $this->artisan('reading-plans:send-reminders')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Overdue, $plan->refresh()->status);
    }

    /**
     * 完了済みの計画は期日を過ぎていても状態が変更されないことを確認する（issue #73）。
     */
    public function test_does_not_change_status_of_completed_plans(): void
    {
        $plan = ReadingPlan::factory()->create([
            'status' => ReadingPlanStatus::Completed,
            'target_date' => today()->subDays(1),
            'completed_at' => now(),
        ]);

        $this->artisan('reading-plans:send-reminders')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Completed, $plan->refresh()->status);
    }

    /**
     * 期日3日前の進行中の計画にリマインダー通知が送信されることを確認する（issue #72）。
     */
    public function test_sends_reminder_three_days_before_due_date(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => today()->addDays(3),
        ]);

        $this->artisan('reading-plans:send-reminders')->assertSuccessful();

        Notification::assertSentTo($user, ReadingPlanReminder::class, function ($notification) use ($plan) {
            $data = $notification->toArray($notification);

            return $data['reading_plan_id'] === $plan->id && $data['timing'] === 'three_days_before';
        });
    }

    /**
     * 期日当日の進行中の計画にリマインダー通知が送信されることを確認する（issue #72）。
     */
    public function test_sends_reminder_on_due_date(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => today(),
        ]);

        $this->artisan('reading-plans:send-reminders')->assertSuccessful();

        Notification::assertSentTo($user, ReadingPlanReminder::class, function ($notification) use ($plan) {
            $data = $notification->toArray($notification);

            return $data['reading_plan_id'] === $plan->id && $data['timing'] === 'on_due_date';
        });
    }

    /**
     * 期日3日後の期限切れの計画に再エンゲージメント通知が送信されることを確認する（issue #73）。
     */
    public function test_sends_re_engagement_reminder_three_days_after_due_date(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Overdue,
            'target_date' => today()->subDays(3),
        ]);

        $this->artisan('reading-plans:send-reminders')->assertSuccessful();

        Notification::assertSentTo($user, ReadingPlanReminder::class, function ($notification) use ($plan) {
            $data = $notification->toArray($notification);

            return $data['reading_plan_id'] === $plan->id && $data['timing'] === 'three_days_after';
        });
    }

    /**
     * まだin_progressのまま期日3日後を迎えた計画は、自動失効と同時に再エンゲージメント
     * 通知も送信されることを確認する（issue #73、二重シナリオ）。
     */
    public function test_marks_overdue_and_sends_re_engagement_reminder_in_same_run(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => today()->subDays(3),
        ]);

        $this->artisan('reading-plans:send-reminders')->assertSuccessful();

        $this->assertSame(ReadingPlanStatus::Overdue, $plan->refresh()->status);
        Notification::assertSentTo($user, ReadingPlanReminder::class, function ($notification) use ($plan) {
            $data = $notification->toArray($notification);

            return $data['reading_plan_id'] === $plan->id && $data['timing'] === 'three_days_after';
        });
    }

    /**
     * リマインダー対象外（3日前・当日・3日後のいずれにも該当しない）の計画には
     * 通知が送信されないことを確認する（issue #72）。
     */
    public function test_does_not_send_reminder_for_out_of_window_plan(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => today()->addDays(7),
        ]);

        $this->artisan('reading-plans:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    /**
     * 完了済みの計画にはリマインダー通知が送信されないことを確認する（issue #72・#73）。
     */
    public function test_does_not_send_reminder_for_completed_plan(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'target_date' => today(),
            'completed_at' => now(),
        ]);

        $this->artisan('reading-plans:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    /**
     * 同一タイミングの通知が既に送信済みの場合は再送信しないことを確認する
     * （バッチ再実行時の重複防止。issue #72・#73の前提となる挙動）。
     */
    public function test_does_not_resend_already_sent_reminder(): void
    {
        $user = User::factory()->create();
        $plan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::InProgress,
            'target_date' => today(),
        ]);

        // 事前に当日分の通知が送信済みの状態を作る。
        $user->notify(new ReadingPlanReminder($plan, 'on_due_date'));

        Notification::fake();

        $this->artisan('reading-plans:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
