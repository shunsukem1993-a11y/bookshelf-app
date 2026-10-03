<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ReviewValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewRequest extends FormRequest
{
    use ReviewValidationRules;

    /**
     * このリクエストを行う権限があるかどうかを判定する。
     *
     * 認可をバリデーションより先に行うため、他人のレビューへの更新は入力内容に関係なく403となる。
     */
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $this->user() !== null && $this->user()->can('update', $review);
    }

    /**
     * バリデーション対象の属性とルールを取得する。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->reviewRules();
    }

    /**
     * バリデーションエラーメッセージを取得する。
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->reviewMessages();
    }
}
