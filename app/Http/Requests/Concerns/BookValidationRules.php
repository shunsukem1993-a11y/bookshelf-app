<?php

namespace App\Http\Requests\Concerns;

trait BookValidationRules
{
    /**
     * ISBN以外の共通バリデーションルールを取得する。
     *
     * @return array<string, mixed>
     */
    protected function commonBookRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'published_date' => ['required', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['required', 'integer', 'exists:genres,id'],
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
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者名は必須です。',
            'author.max' => '著者名は255文字以内で入力してください。',
            'published_date.required' => '出版日は必須です。',
            'published_date.date_format' => '出版日はYYYY-MM-DD形式で入力してください。',
            'description.max' => '説明は255文字以内で入力してください。',
            'image_url.url' => '画像URLはURL形式で入力してください。',
            'image_url.max' => '画像URLは255文字以内で入力してください。',
            'genres.required' => 'ジャンルを1つ以上選択してください。',
            'genres.min' => 'ジャンルを1つ以上選択してください。',
            'genres.*.integer' => 'ジャンルIDは整数で指定してください。',
            'genres.*.exists' => '指定されたジャンルは存在しません。',
        ];
    }
}
