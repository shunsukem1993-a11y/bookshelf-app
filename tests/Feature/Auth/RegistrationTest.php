<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストが会員登録画面を表示でき、ログイン画面へのリンクからログイン画面を開けることを確認する。
     */
    public function test_guest_can_view_registration_form_and_open_login_page(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee(route('register.store'), false);
        $response->assertSee('href="'.route('login').'"', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('login.store'), false);
    }

    /**
     * 有効な値で登録すると、ユーザーが作成され、パスワードがハッシュ化され、ログイン状態になり、書籍一覧（/）へリダイレクトされることを確認する。
     */
    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'yamada@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('山田太郎', $user->name);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotSame('password123', $user->password);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');
    }

    /**
     * 無効な値で登録すると、登録画面へ戻り、エラーが表示され、ユーザーが増えず、未ログインのままであることを確認する。
     *
     * @dataProvider invalidRegistrationPayloadProvider
     *
     * @param  array<string, mixed>  $payload
     */
    public function test_registration_rejects_invalid_payload(array $payload, string $field, string $message): void
    {
        // 「メールが登録済み」の判定に使うため、既存ユーザーを用意する
        User::factory()->create(['email' => 'registered@example.com']);

        $response = $this->from(route('register'))->post(route('register.store'), $payload);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors([$field => $message]);
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    /**
     * 境界値の入力で登録すると、エラーなく登録されることを確認する。
     *
     * @dataProvider validRegistrationBoundaryProvider
     *
     * @param  array<string, mixed>  $payload
     */
    public function test_registration_accepts_boundary_values(array $payload): void
    {
        $response = $this->post(route('register.store'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => strtolower($payload['email'])]);
    }

    /**
     * ログイン済みで会員登録画面を開くと、書籍一覧（/）へリダイレクトされることを確認する。
     */
    public function test_logged_in_user_is_redirected_from_registration_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertRedirect('/');
    }

    /**
     * 無効な入力の一覧（説明 => [入力値, エラーになる項目, メッセージ]）。既存のメッセージのみを対象にする。
     *
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function invalidRegistrationPayloadProvider(): array
    {
        $valid = [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        return [
            '名前が未入力' => [array_merge($valid, ['name' => '']), 'name', '名前は必須です。'],
            '名前が256文字' => [array_merge($valid, ['name' => str_repeat('あ', 256)]), 'name', '名前は255文字以内で入力してください。'],
            'メールが未入力' => [array_merge($valid, ['email' => '']), 'email', 'メールアドレスは必須です。'],
            'メールの形式が不正' => [array_merge($valid, ['email' => 'not-an-email']), 'email', 'メールアドレスはメール形式で入力してください。'],
            'メールが256文字' => [array_merge($valid, ['email' => str_repeat('a', 244).'@example.com']), 'email', 'メールアドレスは255文字以内で入力してください。'],
            'メールが登録済み' => [array_merge($valid, ['email' => 'registered@example.com']), 'email', 'このメールアドレスはすでに登録されています。'],
            'パスワードが未入力' => [array_merge($valid, ['password' => '', 'password_confirmation' => '']), 'password', 'パスワードは必須です。'],
            'パスワードが7文字' => [array_merge($valid, ['password' => 'pass123', 'password_confirmation' => 'pass123']), 'password', 'パスワードは8文字以上で入力してください。'],
            'パスワードが確認用と不一致' => [array_merge($valid, ['password_confirmation' => 'different123']), 'password', 'パスワード確認用と一致しません。'],
        ];
    }

    /**
     * 境界値の有効な入力の一覧。
     *
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function validRegistrationBoundaryProvider(): array
    {
        $valid = [
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        return [
            '名前が255文字' => [array_merge($valid, ['name' => str_repeat('あ', 255), 'email' => 'name255@example.com'])],
            'パスワードが8文字' => [array_merge($valid, ['password' => 'pass1234', 'password_confirmation' => 'pass1234', 'email' => 'pass8@example.com'])],
            'メールが255文字' => [array_merge($valid, ['email' => str_repeat('a', 64).'@'.str_repeat('b', 60).'.'.str_repeat('c', 60).'.'.str_repeat('d', 60).'.xxx.com'])],
        ];
    }
}
