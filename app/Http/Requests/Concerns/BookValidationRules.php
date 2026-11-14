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
            'published_date' => ['nullable', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['required', 'integer', 'exists:genres,id'],
        ];
    }

    /**
     * ISBN以外の共通バリデーションエラーメッセージを取得する。
     *
     * title.required・title.max・author.required・author.max・
     * published_date.date_format・description.max・image_url.url・
     * image_url.maxは、lang/ja/validation.phpの汎用メッセージと
     * 完全に一致するため、ここでは個別に定義しない。
     *
     * @return array<string, string>
     */
    protected function commonBookMessages(): array
    {
        return [
            'genres.required' => 'ジャンルを1つ以上選択してください。',
            'genres.min' => 'ジャンルを1つ以上選択してください。',
            'genres.*.integer' => 'ジャンルIDは整数で指定してください。',
            'genres.*.exists' => '指定されたジャンルは存在しません。',
        ];
    }
}
