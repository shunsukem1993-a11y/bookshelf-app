<?php

namespace App\Http\Requests;

use Laravel\Fortify\Http\Requests\LoginRequest as FortifyLoginRequest;

class LoginRequest extends FortifyLoginRequest
{
    /**
     * バリデーション対象の属性とルールを取得する。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * バリデーションエラーメッセージを取得する。
     *
     * email.required・email.email・email.max・password.requiredは、
     * lang/ja/validation.phpの汎用メッセージと完全に一致するため、
     * ここでは個別に定義しない。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }
}
