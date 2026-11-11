<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    /**
     * @param  string  $timing  通知タイミング（three_days_before, on_due_date, three_days_after）
     */
    public function __construct(
        private readonly ReadingPlan $readingPlan,
        private readonly string $timing,
    ) {}

    /**
     * 通知の送信チャンネルを取得する。
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * データベース通知用の配列表現を取得する。
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'timing' => $this->timing,
            'title' => $this->title(),
            'body' => $this->body(),
        ];
    }

    /**
     * タイミングに応じた通知タイトルを取得する。
     */
    private function title(): string
    {
        return match ($this->timing) {
            'three_days_before' => '読書計画の期日が近づいています',
            'on_due_date' => '読書計画の期日です',
            'three_days_after' => '読書計画の期日を過ぎています',
            default => '読書計画のお知らせ',
        };
    }

    /**
     * タイミングに応じた通知本文を取得する。
     */
    private function body(): string
    {
        $bookTitle = $this->readingPlan->book->title;
        $targetDate = $this->readingPlan->target_date->format('Y-m-d');

        return match ($this->timing) {
            'three_days_before' => "「{$bookTitle}」の期日（{$targetDate}）まであと3日です。",
            'on_due_date' => "「{$bookTitle}」の期日（{$targetDate}）は本日です。",
            'three_days_after' => "「{$bookTitle}」の期日（{$targetDate}）を3日過ぎています。",
            default => "「{$bookTitle}」の読書計画をご確認ください。",
        };
    }
}
