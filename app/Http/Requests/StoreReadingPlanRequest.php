<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\Concerns\ReadingPlanValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * 同一ユーザーが同じ書籍に対して進行中（in_progress）の計画を重複して
     * 作成できないよう、book_idの一意性をuser_id・statusで絞り込んで検証する。
     * 完了済み・期限切れの計画がある書籍への再登録（読み直し）は許可される。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                'exists:books,id',
                Rule::unique('reading_plans', 'book_id')
                    ->where('user_id', $this->user()->id)
                    ->where('status', ReadingPlanStatus::InProgress->value),
            ],
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
            'book_id.unique' => 'この書籍は既に進行中の読書計画があります。',
            ...$this->targetDateMessages(),
        ];
    }
}
