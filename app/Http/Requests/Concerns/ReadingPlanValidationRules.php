<?php

namespace App\Http\Requests\Concerns;

trait ReadingPlanValidationRules
{
    /**
     * 読書計画の登録・編集で共通のバリデーションルールを取得する。
     *
     * @return array<string, mixed>
     */
    protected function targetDateRules(): array
    {
        return [
            'target_date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * 読書計画の登録・編集で共通のバリデーションエラーメッセージを取得する。
     *
     * target_date.required・target_date.date_formatは、
     * lang/ja/validation.phpの汎用メッセージと完全に一致するため、
     * ここでは個別に定義しない。
     *
     * @return array<string, string>
     */
    protected function targetDateMessages(): array
    {
        return [];
    }
}
