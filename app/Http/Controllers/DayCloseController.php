<?php

namespace App\Http\Controllers;

use App\Models\DayClose;
use App\Models\DayExpense;
use App\Models\StaffDayClose;
use App\Models\User;
use App\Services\DayCloseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DayCloseController extends Controller
{
    public function __construct(private DayCloseService $dayClose) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $selectedDate = isset($validated['date'])
            ? Carbon::parse($validated['date'])->startOfDay()
            : now()->startOfDay();

        $isOwner = $user->canCloseBusinessDay();
        $forUser = $isOwner ? $user : $user;

        $summary = $this->dayClose->buildSummary($selectedDate, $forUser);
        $existingClose = DayClose::query()
            ->with('closedBy')
            ->whereDate('business_date', $selectedDate)
            ->first();

        $personalClose = StaffDayClose::query()
            ->with('user')
            ->whereDate('business_date', $selectedDate)
            ->where('user_id', $user->id)
            ->first();

        $staffCloses = collect();
        $allSubmissions = collect();
        $pendingStaff = collect();
        $ownerCanClose = false;
        $ownerSummary = null;
        $staffWithActivity = collect();
        $ownerHasActivity = false;
        $ownerMustClosePersonal = false;

        if ($isOwner && ! $existingClose) {
            $allCloses = StaffDayClose::query()
                ->with('user')
                ->whereDate('business_date', $selectedDate)
                ->orderBy('closed_at')
                ->get();

            $staffCloses = $allCloses->filter(fn (StaffDayClose $close) => $close->user?->isStaff());
            $allSubmissions = $allCloses;

            $staffWithActivity = $this->dayClose->staffWithActivity($selectedDate);
            $pendingStaff = $this->dayClose->pendingStaffForDate($selectedDate);
            $ownerHasActivity = $this->dayClose->userHasActivity($user, $selectedDate);
            $ownerMustClosePersonal = $ownerHasActivity && ! $personalClose;
            $ownerCanClose = $this->dayClose->ownerCanCloseBusinessDay($user, $selectedDate);

            if ($allCloses->isNotEmpty()) {
                $ownerSummary = $this->dayClose->aggregateStaffCloseSummaries($allCloses);
            }
        }

        $expenses = $this->resolveExpenses($user, $selectedDate, $existingClose, $personalClose, $forUser);

        $history = $isOwner
            ? DayClose::query()->with('closedBy')->latest('business_date')->latest('id')->paginate(10)->withQueryString()
            : StaffDayClose::query()->with('user')->where('user_id', $user->id)->latest('business_date')->latest('id')->paginate(10)->withQueryString();

        $unclosedPreviousDays = $isOwner
            ? $this->dayClose->unclosedPreviousBusinessDays()
            : $this->dayClose->unclosedPreviousPersonalDays($user);

        return view('day-closes.index', compact(
            'selectedDate',
            'summary',
            'existingClose',
            'personalClose',
            'staffCloses',
            'allSubmissions',
            'pendingStaff',
            'ownerCanClose',
            'ownerSummary',
            'staffWithActivity',
            'ownerHasActivity',
            'ownerMustClosePersonal',
            'expenses',
            'history',
            'isOwner',
            'unclosedPreviousDays',
        ));
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_date' => ['required', 'date'],
            'category' => ['required', Rule::in(array_keys(DayExpense::categoryOptions()))],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
        ]);

        try {
            $this->dayClose->addExpense(
                $request->user(),
                Carbon::parse($validated['business_date'])->startOfDay(),
                $validated['category'],
                (float) $validated['amount'],
                $validated['description'] ?? null,
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()
                ->route('day-closes.index', ['date' => $validated['business_date']])
                ->withInput()
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()
            ->route('day-closes.index', ['date' => $validated['business_date']])
            ->with('success', 'Expense recorded.');
    }

    public function destroyExpense(Request $request, DayExpense $expense): RedirectResponse
    {
        $date = $expense->business_date->format('Y-m-d');

        try {
            $this->dayClose->removeExpense($request->user(), $expense);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()
                ->route('day-closes.index', ['date' => $date])
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()
            ->route('day-closes.index', ['date' => $date])
            ->with('success', 'Expense removed.');
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $close = $this->dayClose->closeStaffDay(
                $request->user(),
                Carbon::parse($validated['business_date'])->startOfDay(),
                $validated['notes'] ?? null,
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()
                ->route('day-closes.index', ['date' => $validated['business_date']])
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        $message = $request->user()->isOwner()
            ? 'Your personal day has been closed.'
            : 'Your day has been closed and sent to the owner.';

        return redirect()
            ->route('day-closes.index', ['date' => $close->business_date->format('Y-m-d')])
            ->with('success', $message);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $close = $this->dayClose->closeBusinessDay(
                $request->user(),
                Carbon::parse($validated['business_date'])->startOfDay(),
                $validated['notes'] ?? null,
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()
                ->route('day-closes.index', ['date' => $validated['business_date']])
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()
            ->route('day-closes.index', ['date' => $close->business_date->format('Y-m-d')])
            ->with('success', 'Business day closed for '.$close->formattedBusinessDate().'.');
    }

    /** @return \Illuminate\Support\Collection<int, DayExpense|array<string, mixed>> */
    private function resolveExpenses(User $user, Carbon $selectedDate, ?DayClose $existingClose, ?StaffDayClose $personalClose, User $forUser)
    {
        if ($existingClose) {
            return collect($existingClose->summary['expenses'] ?? []);
        }

        if ($personalClose) {
            return collect($personalClose->summary['expenses'] ?? []);
        }

        return DayExpense::query()
            ->with('recordedBy')
            ->whereDate('business_date', $selectedDate)
            ->where('recorded_by_user_id', $forUser->id)
            ->orderBy('id')
            ->get();
    }
}
