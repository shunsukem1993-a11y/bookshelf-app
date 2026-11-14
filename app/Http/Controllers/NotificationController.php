<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
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
     * 存在しない通知IDの場合は404、他人の通知IDを指定した場合はDatabaseNotificationPolicyにより403となる。
     */
    public function markAsRead(string $id): RedirectResponse
    {
        $notification = DatabaseNotification::findOrFail($id);

        $this->authorize('markAsRead', $notification);

        $notification->markAsRead();

        return redirect()->route('notifications.index');
    }
}
