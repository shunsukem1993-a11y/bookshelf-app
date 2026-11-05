<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * 通知一覧を表示する（認証必須）。
     *
     * 仮実装：ナビゲーションの参照先（route('notifications.index')）を解決するための
     * 最小限のルートであり、常に空の一覧を返す。本実装はIssue #70で対応する。
     */
    public function index(): View
    {
        $notifications = collect();

        return view('notifications.index', compact('notifications'));
    }
}
