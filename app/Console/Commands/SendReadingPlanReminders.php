<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SendReadingPlanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading-plans:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '期日を経過した読書計画を期限切れにし、期日前後の読書計画にリマインダー通知を送信する';

    /**
     * コマンドを実行する。
     */
    public function handle(): int
    {
        $this->markOverduePlans();
        $this->sendReminders();

        return self::SUCCESS;
    }

    /**
     * 期日を経過した進行中の読書計画を「期限切れ」に変更する。
     */
    private function markOverduePlans(): void
    {
        $overduePlans = ReadingPlan::where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', '<', today())
            ->get();

        if ($overduePlans->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($overduePlans) {
            $overduePlans->each(fn (ReadingPlan $plan) => $plan->update([
                'status' => ReadingPlanStatus::Overdue,
            ]));
        });
    }

    /**
     * 期日の3日前・当日・3日後に該当する読書計画へリマインダー通知を送信する。
     *
     * markOverduePlans()より後に実行するため、本メソッド内で新たにDBへ問い合わせる
     * ことで、今回失効した計画（3日後ケース）のステータスも正しく反映される。
     */
    private function sendReminders(): void
    {
        $timingsByDate = $this->timingsByDate();

        ReadingPlan::with(['user', 'book'])
            ->whereIn('status', [ReadingPlanStatus::InProgress->value, ReadingPlanStatus::Overdue->value])
            ->get()
            ->filter(fn (ReadingPlan $plan) => $timingsByDate->has($plan->target_date->toDateString()))
            ->each(function (ReadingPlan $plan) use ($timingsByDate) {
                $timing = $timingsByDate->get($plan->target_date->toDateString());

                $this->notifyUnlessAlreadySent($plan, $timing);
            });
    }

    /**
     * 日付をキー、通知タイミングを値としたCollectionを取得する。
     *
     * @return Collection<string, string>
     */
    private function timingsByDate(): Collection
    {
        return collect([
            today()->addDays(3)->toDateString() => 'three_days_before',
            today()->toDateString() => 'on_due_date',
            today()->subDays(3)->toDateString() => 'three_days_after',
        ]);
    }

    /**
     * 同じ読書計画・タイミングの通知が未送信の場合のみ送信する（バッチ再実行時の重複防止）。
     */
    private function notifyUnlessAlreadySent(ReadingPlan $plan, string $timing): void
    {
        $alreadySent = $plan->user->notifications()
            ->where('data->reading_plan_id', $plan->id)
            ->where('data->timing', $timing)
            ->exists();

        if (! $alreadySent) {
            $plan->user->notify(new ReadingPlanReminder($plan, $timing));
        }
    }
}
