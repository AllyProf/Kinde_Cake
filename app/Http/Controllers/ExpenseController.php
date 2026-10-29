<?php

namespace App\Http\Controllers;

use App\Models\DayExpense;
use App\Services\DayCloseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(private DayCloseService $dayClose) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'category' => ['nullable', Rule::in(array_keys(DayExpense::categoryOptions()))],
        ]);

        $user = $request->user();
        $selectedDate = isset($validated['date'])
            ? Carbon::parse($validated['date'])->startOfDay()
            : now()->startOfDay();
        $category = $validated['category'] ?? null;

        $query = DayExpense::query()
            ->with('recordedBy')
            ->whereDate('business_date', $selectedDate)
            ->when($category, fn ($builder) => $builder->where('category', $category))
            ->when(
                ! $user->isOwner(),
                fn ($builder) => $builder->where('recorded_by_user_id', $user->id)
            )
            ->latest('id');

        $expenses = $query->get();
        $totalAmount = round((float) $expenses->sum('amount'), 2);

        $businessClosed = $this->dayClose->businessDayIsClosed($selectedDate);
        $personalClosed = $this->dayClose->staffDayIsClosed($user, $selectedDate);
        $canManage = $user->hasPermission('expenses.manage')
            && ! $selectedDate->isFuture()
            && ! $businessClosed
            && ! $personalClosed;

        return view('expenses.index', [
            'expenses' => $expenses,
            'selectedDate' => $selectedDate,
            'category' => $category,
            'totalAmount' => $totalAmount,
            'canManage' => $canManage,
            'businessClosed' => $businessClosed,
            'personalClosed' => $personalClosed,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_date' => ['required', 'date'],
            'category' => ['required', Rule::in(array_keys(DayExpense::categoryOptions()))],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
        ]);

        $date = $validated['business_date'];

        try {
            $this->dayClose->addExpense(
                $request->user(),
                Carbon::parse($date)->startOfDay(),
                $validated['category'],
                (float) $validated['amount'],
                $validated['description'] ?? null,
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()
                ->route('expenses.index', ['date' => $date])
                ->withInput()
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()
            ->route('expenses.index', ['date' => $date])
            ->with('success', 'Expense recorded.');
    }

    public function destroy(Request $request, DayExpense $expense): RedirectResponse
    {
        $date = $expense->business_date->format('Y-m-d');

        try {
            $this->dayClose->removeExpense($request->user(), $expense);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()
                ->route('expenses.index', ['date' => $date])
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()
            ->route('expenses.index', ['date' => $date])
            ->with('success', 'Expense removed.');
    }
}
