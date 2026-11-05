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
     * @return array<string, string>
     */
    protected function targetDateMessages(): array
    {
        return [
            'target_date.required' => '期日は必須です。',
            'target_date.date_format' => '期日はYYYY-MM-DD形式で入力してください。',
        ];
    }
}
