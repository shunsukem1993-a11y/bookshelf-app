<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    /**
     * 読書計画を6件投入する（★応用）。
     *
     * 採点者がいつ実行しても同じ挙動になるよう、Carbon::today()を起点に
     * target_dateを動的に設定する。動作確認の効率のため、主要シナリオは
     * 山田太郎（1ユーザー）に集約し、他ユーザー認可テスト用に鈴木花子の
     * 計画を1件追加する。
     *
     * book_idは計画ごとに異なる書籍を割り当てる（同一ユーザーのin_progress
     * 重複制御に抵触しないよう。重複制御自体はController実装時に別途設計する）。
     */
    public function run(): void
    {
        $yamada = User::where('email', 'yamada@example.com')->first();
        $suzuki = User::where('email', 'suzuki@example.com')->first();
        $books = Book::orderBy('id')->take(6)->get();

        // 1. 3日前リマインダー対象
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[0]->id,
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 2. 当日リマインダー対象
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[1]->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 3. バッチでAuto-expire化 + 3日後再エンゲージメント対象（二重シナリオ）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[2]->id,
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 4. リマインダー対象外
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[3]->id,
            'target_date' => Carbon::today()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        // 5. 完了済み
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $books[4]->id,
            'target_date' => Carbon::today()->subDays(10),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => Carbon::today()->subDays(5),
        ]);

        // 6. 他ユーザー認可テスト用（山田太郎ログイン中に/reading-plans/{id}/edit直打ちで403確認）
        ReadingPlan::create([
            'user_id' => $suzuki->id,
            'book_id' => $books[5]->id,
            'target_date' => Carbon::today()->addDays(5),
            'status' => ReadingPlanStatus::InProgress,
        ]);
    }
}
