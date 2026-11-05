<?php

namespace Tests\Unit\Models;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ReadingPlan N : 1 User（計画者）を確認する。
     */
    public function test_reading_plan_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $readingPlan->user);
        $this->assertEquals($user->id, $readingPlan->user->id);
    }

    /**
     * ReadingPlan N : 1 Book（対象書籍）を確認する。
     */
    public function test_reading_plan_belongs_to_book(): void
    {
        $book = Book::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['book_id' => $book->id]);

        $this->assertInstanceOf(Book::class, $readingPlan->book);
        $this->assertEquals($book->id, $readingPlan->book->id);
    }

    /**
     * target_date・completed_atが日時としてキャストされることを確認する。
     */
    public function test_dates_are_cast_correctly(): void
    {
        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => '2027-01-01',
            'completed_at' => '2027-01-02 10:00:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $readingPlan->target_date);
        $this->assertSame('2027-01-01', $readingPlan->target_date->toDateString());
        $this->assertInstanceOf(Carbon::class, $readingPlan->completed_at);
    }

    /**
     * statusがReadingPlanStatus Enumとしてキャストされ、既定値がInProgressであることを確認する。
     */
    public function test_status_defaults_to_in_progress_and_is_cast_to_enum(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $this->assertInstanceOf(ReadingPlanStatus::class, $readingPlan->status);
        $this->assertSame(ReadingPlanStatus::InProgress, $readingPlan->status);
    }

    /**
     * statusに各Enum値を設定できることを確認する。
     */
    public function test_status_can_be_set_to_each_enum_value(): void
    {
        $overdue = ReadingPlan::factory()->create(['status' => ReadingPlanStatus::Overdue]);
        $completed = ReadingPlan::factory()->create(['status' => ReadingPlanStatus::Completed]);

        $this->assertSame(ReadingPlanStatus::Overdue, $overdue->refresh()->status);
        $this->assertSame(ReadingPlanStatus::Completed, $completed->refresh()->status);
    }
}
