<?php

namespace App\Http\Controllers;

use App\Services\ReadingReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(private readonly ReadingReportService $readingReportService) {}

    /**
     * マイ読書レポートを表示する（認証必須）。
     *
     * ログインユーザー自身のレビューを基に、基本サマリー・評価分布・
     * 高評価書籍TOP5・ジャンル別評価傾向TOP5を算出する。
     */
    public function index(Request $request): View
    {
        $stats = $this->readingReportService->buildReport($request->user());

        return view('reports.index', compact('stats'));
    }
}
