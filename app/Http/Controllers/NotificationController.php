<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * 通知一覧を表示する（認証必須）。
     *
     * Notifiableトレイトのnotifications()は標準でcreated_at降順のため、
     * 新しい通知から表示される。
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->get();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 通知を既読にする（認証必須。本人の通知のみ）。
     *
     * 本人の通知に限定して検索するため、他人の通知IDを指定した場合は404となる。
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return redirect()->route('notifications.index');
    }
}
