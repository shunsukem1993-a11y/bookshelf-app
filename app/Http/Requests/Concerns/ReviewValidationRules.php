<?php

namespace App\Http\Requests\Concerns;

trait ReviewValidationRules
{
    /**
     * レビューの投稿・編集で共通のバリデーションルールを取得する。
     *
     * @return array<string, mixed>
     */
    protected function reviewRules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * レビューの投稿・編集で共通のバリデーションエラーメッセージを取得する。
     *
     * @return array<string, string>
     */
    protected function reviewMessages(): array
    {
        return [
            'rating.required' => '評価は必須です。',
            'rating.integer' => '評価は1〜5で選択してください。',
            'rating.between' => '評価は1〜5で選択してください。',
            'comment.required' => 'コメントは必須です。',
            'comment.string' => 'コメントは文字列で入力してください。',
            'comment.max' => 'コメントは255文字以内で入力してください。',
        ];
    }
}
