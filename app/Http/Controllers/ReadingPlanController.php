<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * 読書計画一覧を表示する（認証必須）。状態による絞り込みに対応する。
     */
    public function index(Request $request): View
    {
        $currentStatus = $request->query('status');

        $readingPlans = $request->user()->readingPlans()
            ->with('book')
            ->when($currentStatus, fn ($query) => $query->where('status', $currentStatus))
            ->orderBy('target_date')
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 読書計画作成フォームを表示する（認証必須）。
     */
    public function create(): View
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 読書計画を登録する（認証必須）。
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $request->user()->readingPlans()->create($request->validated());

        return redirect()->route('reading-plans.index')->with('success', '読書計画を作成しました。');
    }

    /**
     * 読書計画編集フォームを表示する（作成者本人かつ未完了のみ。ReadingPlanPolicy@updateで認可）。
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画を更新する（作成者本人かつ未完了のみ。認可はUpdateReadingPlanRequestで行う）。
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        $readingPlan->update($request->validated());

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました。');
    }

    /**
     * 読書計画を削除する（作成者本人のみ。ReadingPlanPolicy@deleteで認可）。
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }

    /**
     * 読書計画を読了済みにする（作成者本人かつ未完了のみ。ReadingPlanPolicy@updateで認可）。
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を読了済みにしました。');
    }
}
