<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ReadingPlanValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreReadingPlanRequest extends FormRequest
{
    use ReadingPlanValidationRules;

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
            'book_id' => ['required', 'integer', 'exists:books,id'],
            ...$this->targetDateRules(),
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
            'book_id.required' => '書籍を選択してください。',
            'book_id.exists' => '指定された書籍は存在しません。',
            ...$this->targetDateMessages(),
        ];
    }
}
