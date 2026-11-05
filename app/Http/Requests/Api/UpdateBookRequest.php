<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\ApiBookValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    use ApiBookValidationRules;

    /**
     * このリクエストを行う権限があるかどうかを判定する。
     *
     * 基本フェーズでは認証・所有者チェックなしのため、常に許可する
     * （応用フェーズでSanctum + BookPolicy導入時に見直す）。
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーション対象の属性とルールを取得する。
     *
     * ISBNの一意性チェックでは更新対象自身のレコードを除外する。
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
