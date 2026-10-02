<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * このリクエストを行う権限があるかどうかを判定する。
     *
     * 'auth'ミドルウェアで未認証アクセスは弾くため、ここでは常に許可する。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーション対象の属性とルールを取得する。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'digits:13', 'unique:books,isbn'],
            'published_date' => ['required', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['required', 'integer', 'exists:genres,id'],
        ];
    }

    /**
     * バリデーションエラーメッセージを取得する。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'author.required' => '著者名は必須です。',
            'isbn.required' => 'ISBNは必須です。',
            'isbn.digits' => 'ISBNは13桁で入力してください。',
            'isbn.unique' => '入力されたISBNはすでに登録されています。',
            'published_date.required' => '出版日は必須です。',
            'published_date.date_format' => '出版日はYYYY-MM-DD形式で入力してください。',
            'description.max' => '説明は255文字以内で入力してください。',
            'image_url.url' => '画像URLはURL形式で入力してください。',
            'image_url.max' => '画像URLは255文字以内で入力してください。',
            'genres.required' => 'ジャンルを1つ以上選択してください。',
            'genres.min' => 'ジャンルを1つ以上選択してください。',
            'genres.*.exists' => '指定されたジャンルは存在しません。',
        ];
    }
}
