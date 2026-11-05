<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧を表示する（認証必須）。
     *
     * 仮実装：ナビゲーションの参照先（route('reading-plans.index')）を解決するための
     * 最小限のルートであり、常に空の一覧を返す。本実装はIssue #66で対応する。
     */
    public function index(): View
    {
        $readingPlans = collect();
        $currentStatus = null;

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画作成フォームへのアクセスを一覧へリダイレクトする。
     *
     * 仮実装：一覧画面内の「新規計画作成」リンク（route('reading-plans.create')）を
     * 解決するための最小限のルートであり、本実装はIssue #66で対応する。
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('reading-plans.index')->with('success', '読書計画作成機能は準備中です。');
    }
}
