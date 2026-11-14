<?php

namespace Tests\Feature\Notifications;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationMarkAsReadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 所有者が自分の通知を既読にできることを確認する。
     */
    public function test_owner_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['user_id' => $user->id]);
        $user->notify(new ReadingPlanReminder($readingPlan, 'on_due_date'));
        $notification = $user->notifications()->first();

        $response = $this->actingAs($user)->post(route('notifications.read', $notification->id));

        $response->assertRedirect(route('notifications.index'));
        $this->assertNotNull($notification->refresh()->read_at);
    }

    /**
     * 他ユーザーの通知を既読にしようとすると403になり、既読状態が変わらないことを確認する。
     */
    public function test_other_user_cannot_mark_as_read(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['user_id' => $owner->id]);
        $owner->notify(new ReadingPlanReminder($readingPlan, 'on_due_date'));
        $notification = $owner->notifications()->first();

        $response = $this->actingAs($otherUser)->post(route('notifications.read', $notification->id));

        $response->assertForbidden();
        $this->assertNull($notification->refresh()->read_at);
    }

    /**
     * 存在しない通知IDを指定すると404になることを確認する。
     */
    public function test_nonexistent_notification_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('notifications.read', (string) Str::uuid()));

        $response->assertNotFound();
    }

    /**
     * 未認証でアクセスすると/loginにリダイレクトされることを確認する。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $user = User::factory()->create();
        $readingPlan = ReadingPlan::factory()->create(['user_id' => $user->id]);
        $user->notify(new ReadingPlanReminder($readingPlan, 'on_due_date'));
        $notification = $user->notifications()->first();

        $response = $this->post(route('notifications.read', $notification->id));

        $response->assertRedirect(route('login'));
    }
}
