<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\BookValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    use BookValidationRules;

    /**
     * このリクエストを行う権限があるかどうかを判定する。
     *
     * 認可をバリデーションより先に行うため、作成者以外の更新は入力内容に関係なく403となる。
     */
    public function authorize(): bool
    {
        $book = $this->route('book');

        return $this->user() !== null && $this->user()->can('update', $book);
    }

    /**
     * バリデーション対象の属性とルールを取得する。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->commonBookRules(),
            'isbn' => [
                'required',
                'string',
                'digits:13',
                Rule::unique('books', 'isbn')->ignore($this->route('book')),
            ],
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
            ...$this->commonBookMessages(),
            'isbn.required' => 'ISBNは必須です。',
            'isbn.digits' => 'ISBNは13桁で入力してください。',
            'isbn.unique' => '入力されたISBNはすでに登録されています。',
        ];
    }
}
