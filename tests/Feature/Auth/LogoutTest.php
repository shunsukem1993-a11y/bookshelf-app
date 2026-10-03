<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログイン中にログアウトすると、未ログインになり、ログイン画面へリダイレクトされることを確認する。
     */
    public function test_logged_in_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /**
     * ゲストがログアウトしても、エラーにならずログイン画面へリダイレクトされることを確認する。
     */
    public function test_guest_logout_redirects_to_login(): void
    {
        $response = $this->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
