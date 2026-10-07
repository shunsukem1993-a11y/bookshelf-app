<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewLikeController extends Controller
{
    /**
     * レビューのいいねを切り替える（認証必須）。登録済みなら解除し、未登録なら追加する。
     *
     * 元の画面（書籍詳細）へ戻る。
     */
    public function toggle(Request $request, Review $review): RedirectResponse
    {
        $request->user()->likedReviews()->toggle($review->id);

        return redirect()->back();
    }
}
