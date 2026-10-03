<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストがログイン画面を表示でき、会員登録画面へのリンクからその画面を開けることを確認する。
     */
    public function test_guest_can_view_login_form_and_open_registration_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(route('login.store'), false);
        $response->assertSee('href="'.route('register').'"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee(route('register.store'), false);
    }

    /**
     * 正しいメールとパスワードでログインでき、書籍一覧（/）へリダイレクトされることを確認する。
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'yamada@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'yamada@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    /**
     * 無効な入力でログインすると、ログイン画面へ戻り、エラーが表示され、未ログインのままであることを確認する。
     *
     * @dataProvider invalidLoginPayloadProvider
     *
     * @param  array<string, mixed>  $payload
     */
    public function test_login_rejects_invalid_payload(array $payload, string $field, string $message): void
    {
        $response = $this->from(route('login'))->post(route('login.store'), $payload);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([$field => $message]);
        $this->assertGuest();
    }

    /**
     * パスワードが違う場合、「メールアドレスまたはパスワードが正しくありません。」が表示され、未ログインのままであることを確認する。
     */
    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'yamada@example.com',
            'password' => 'password123',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'yamada@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。']);
        $this->assertGuest();
    }

    /**
     * 登録されていないメールの場合も、同じエラーメッセージが表示され、未ログインのままであることを確認する（メールの存在を漏らさない）。
     */
    public function test_login_fails_with_unregistered_email_with_same_message(): void
    {
        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'unknown@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。']);
        $this->assertGuest();
    }

    /**
     * ログイン済みでログイン画面を開くと、書籍一覧（/）へリダイレクトされることを確認する。
     */
    public function test_logged_in_user_is_redirected_from_login_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect('/');
    }

    /**
     * 無効な入力の一覧（説明 => [入力値, エラーになる項目, メッセージ]）。既存のメッセージのみを対象にする。
     *
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function invalidLoginPayloadProvider(): array
    {
        return [
            'メールが未入力' => [['email' => '', 'password' => 'password123'], 'email', 'メールアドレスは必須です。'],
            'メールの形式が不正' => [['email' => 'not-an-email', 'password' => 'password123'], 'email', 'メールアドレスはメール形式で入力してください。'],
            'メールが256文字' => [['email' => str_repeat('a', 244).'@example.com', 'password' => 'password123'], 'email', 'メールアドレスは255文字以内で入力してください。'],
            'パスワードが未入力' => [['email' => 'yamada@example.com', 'password' => ''], 'password', 'パスワードは必須です。'],
        ];
    }
}
