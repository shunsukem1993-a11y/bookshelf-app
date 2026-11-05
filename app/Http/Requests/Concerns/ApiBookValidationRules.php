<?php

namespace App\Http\Requests\Concerns;

trait ApiBookValidationRules
{
    /**
     * ISBN以外の共通バリデーションルールを取得する。
     *
     * 基本フェーズは認証なしのため、登録者を示すuser_idをリクエストボディで検証する。
     * ジャンルはWeb版の"genres"とは異なり、仕様書の表記に合わせ"genre_id"とする。
     *
     * @return array<string, mixed>
     */
    protected function commonBookRules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'published_date' => ['nullable', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genre_id' => ['required', 'array', 'min:1'],
            'genre_id.*' => ['required', 'integer', 'exists:genres,id'],
        ];
    }

    /**
     * ISBN以外の共通バリデーションエラーメッセージを取得する。
     *
     * @return array<string, string>
     */
    protected function commonBookMessages(): array
    {
        return [
            'user_id.required' => 'ユーザーIDは必須です。',
            'user_id.integer' => 'ユーザーIDは整数で指定してください。',
            'user_id.exists' => '指定されたユーザーは存在しません。',
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者名は必須です。',
            'author.max' => '著者名は255文字以内で入力してください。',
            'published_date.date_format' => '出版日はYYYY-MM-DD形式で入力してください。',
            'description.max' => '説明は255文字以内で入力してください。',
            'image_url.url' => '画像URLはURL形式で入力してください。',
            'image_url.max' => '画像URLは255文字以内で入力してください。',
            'genre_id.required' => 'ジャンルを1つ以上選択してください。',
            'genre_id.min' => 'ジャンルを1つ以上選択してください。',
            'genre_id.*.integer' => 'ジャンルIDは整数で指定してください。',
            'genre_id.*.exists' => '指定されたジャンルは存在しません。',
        ];
    }
}
