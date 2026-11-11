<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ReadingPlanValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReadingPlanRequest extends FormRequest
{
    use ReadingPlanValidationRules;

    /**
     * このリクエストを行う権限があるかどうかを判定する。
     *
     * 認可をバリデーションより先に行うため、作成者以外・完了済みの計画の更新は
     * 入力内容に関係なく403となる（ReadingPlanPolicy@updateで判定）。
     */
    public function authorize(): bool
    {
        $readingPlan = $this->route('readingPlan');

        return $this->user() !== null && $this->user()->can('update', $readingPlan);
    }

    /**
     * バリデーション対象の属性とルールを取得する。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->targetDateRules();
    }

    /**
     * バリデーションエラーメッセージを取得する。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->targetDateMessages();
    }
}
