<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\ApiBookValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    use ApiBookValidationRules;

    /**
     * このリクエストを行う権限があるかどうかを判定する。
     *
     * 基本フェーズでは認証不要のため、常に許可する
     * （応用フェーズでSanctum導入時に見直す）。
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
            ...$this->commonBookRules(),
            'isbn' => ['required', 'string', 'digits:13', 'unique:books,isbn'],
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
