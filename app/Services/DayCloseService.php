<?php

namespace App\Services;

use App\Models\DayClose;
use App\Models\DayExpense;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\StaffDayClose;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DayCloseService
{
    /** @return array<string, mixed> */
    public function buildSummary(Carbon $businessDate, ?User $forUser = null): array
    {
        $date = $businessDate->toDateString();

        $salesQuery = Sale::query()
            ->realSales()
            ->whereDate('sold_at', $date)
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED]);

        if ($forUser) {
            $salesQuery->where('user_id', $forUser->id);
        }

        $sales = (clone $salesQuery)->get(['id', 'total', 'amount_paid', 'status']);

        $paymentsQuery = SalePayment::query()
            ->with('paymentProvider:id,name,type')
            ->whereDate('paid_at', $date)
            ->whereHas('sale', fn ($query) => $query->realSales()->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED]));

        if ($forUser) {
            $paymentsQuery->where('user_id', $forUser->id);
        }

        $payments = $paymentsQuery->get(['id', 'amount', 'payment_method', 'payment_provider_id']);

        $cashCollected = 0.0;
        $mobileCollected = 0.0;
        $bankCollected = 0.0;
        $creditRecorded = 0.0;
        $mobileProviders = [];
        $bankProviders = [];

        foreach ($payments as $payment) {
            $amount = (float) $payment->amount;

            if ($payment->payment_method === Sale::PAYMENT_CASH) {
                $cashCollected += $amount;
                continue;
            }

            if ($payment->payment_method === Sale::PAYMENT_CREDIT) {
                $creditRecorded += $amount;
                continue;
            }

            if ($payment->payment_method === Sale::PAYMENT_MOBILE) {
                $mobileCollected += $amount;
                $key = $payment->payment_provider_id ?: 'none';
                if (! isset($mobileProviders[$key])) {
                    $mobileProviders[$key] = [
                        'name' => $payment->paymentProvider?->name ?? 'Unspecified mobile',
                        'amount' => 0.0,
                        'count' => 0,
                    ];
                }
                $mobileProviders[$key]['amount'] += $amount;
                $mobileProviders[$key]['count']++;
                continue;
            }

            if ($payment->payment_method === Sale::PAYMENT_BANK) {
                $bankCollected += $amount;
                $key = $payment->payment_provider_id ?: 'none';
                if (! isset($bankProviders[$key])) {
                    $bankProviders[$key] = [
                        'name' => $payment->paymentProvider?->name ?? 'Unspecified bank',
                        'amount' => 0.0,
                        'count' => 0,
                    ];
                }
                $bankProviders[$key]['amount'] += $amount;
                $bankProviders[$key]['count']++;
                continue;
            }

            $cashCollected += $amount;
        }

        $formatProviders = fn (array $providers) => collect($providers)
            ->map(fn (array $row) => [
                'name' => $row['name'],
                'amount' => round($row['amount'], 2),
                'count' => $row['count'],
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $pendingSales = $sales->filter(fn (Sale $sale) => $sale->hasOutstandingBalance());
        $paidSales = $sales->filter(fn (Sale $sale) => $sale->isPaid());

        $expensesQuery = DayExpense::query()
            ->with('recordedBy:id,name')
            ->whereDate('business_date', $date)
            ->orderBy('id');

        if ($forUser) {
            $expensesQuery->where('recorded_by_user_id', $forUser->id);
        }

        $expenses = $expensesQuery->get();

        $expensesTotal = round((float) $expenses->sum('amount'), 2);
        $expensesByCategory = $expenses
            ->groupBy('category')
            ->map(fn ($rows, $category) => [
                'category' => $category,
                'label' => DayExpense::categoryOptions()[$category] ?? ucfirst($category),
                'amount' => round((float) $rows->sum('amount'), 2),
                'count' => $rows->count(),
            ])
            ->sortByDesc('amount')
            ->values()
            ->all();

        $totalCollected = round($cashCollected + $mobileCollected + $bankCollected, 2);

        return [
            'user_id' => $forUser?->id,
            'user_name' => $forUser?->name,
            'sales_count' => $sales->count(),
            'sales_total' => round((float) $sales->sum('total'), 2),
            'payments_count' => $payments->count(),
            'cash_collected' => round($cashCollected, 2),
            'mobile_collected' => round($mobileCollected, 2),
            'mobile_providers' => $formatProviders($mobileProviders),
            'bank_collected' => round($bankCollected, 2),
            'bank_providers' => $formatProviders($bankProviders),
            'credit_recorded' => round($creditRecorded, 2),
            'total_collected' => $totalCollected,
            'expenses_count' => $expenses->count(),
            'expenses_total' => $expensesTotal,
            'expenses_by_category' => $expensesByCategory,
            'expenses' => $expenses->map(fn (DayExpense $expense) => [
                'id' => $expense->id,
                'category' => $expense->category,
                'label' => $expense->categoryLabel(),
                'description' => $expense->description,
                'amount' => round((float) $expense->amount, 2),
                'recorded_by' => $expense->recordedBy?->name,
            ])->values()->all(),
            'net_in_hand' => round($totalCollected - $expensesTotal, 2),
            'pending_sales_count' => $pendingSales->count(),
            'pending_sales_total' => round((float) $pendingSales->sum(fn (Sale $sale) => $sale->balanceDue()), 2),
            'paid_sales_count' => $paidSales->count(),
        ];
    }

    /** @return Collection<int, User> */
    public function staffWithActivity(Carbon $businessDate): Collection
    {
        $date = $businessDate->toDateString();

        $saleUserIds = Sale::query()
            ->realSales()
            ->whereDate('sold_at', $date)
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $paymentUserIds = SalePayment::query()
            ->whereDate('paid_at', $date)
            ->whereHas('sale', fn ($query) => $query->realSales()->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED]))
            ->distinct()
            ->pluck('user_id');

        $expenseUserIds = DayExpense::query()
            ->whereDate('business_date', $date)
            ->distinct()
            ->pluck('recorded_by_user_id');

        $userIds = $saleUserIds
            ->merge($paymentUserIds)
            ->merge($expenseUserIds)
            ->unique()
            ->filter();

        return User::query()
            ->whereIn('id', $userIds)
            ->where('role', User::ROLE_STAFF)
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, array{date: string, label: string}> */
    public function unclosedPreviousBusinessDays(?Carbon $throughDate = null): Collection
    {
        $throughDate = ($throughDate ?? now())->copy()->startOfDay();
        $lastDate = $throughDate->copy()->subDay();

        if ($lastDate->isBefore(Carbon::parse('2020-01-01'))) {
            return collect();
        }

        $closedDates = DayClose::query()
            ->whereDate('business_date', '<=', $lastDate)
            ->pluck('business_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        return $this->businessActivityDates($lastDate)
            ->reject(fn (string $date) => $closedDates->has($date))
            ->sortDesc()
            ->values()
            ->map(fn (string $date) => [
                'date' => $date,
                'label' => Carbon::parse($date)->format('d M Y'),
            ]);
    }

    /** @return Collection<int, array{date: string, label: string}> */
    public function unclosedPreviousPersonalDays(User $user, ?Carbon $throughDate = null): Collection
    {
        $throughDate = ($throughDate ?? now())->copy()->startOfDay();
        $lastDate = $throughDate->copy()->subDay();

        if ($lastDate->isBefore(Carbon::parse('2020-01-01'))) {
            return collect();
        }

        $closedDates = StaffDayClose::query()
            ->where('user_id', $user->id)
            ->whereDate('business_date', '<=', $lastDate)
            ->pluck('business_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        $businessClosedDates = DayClose::query()
            ->whereDate('business_date', '<=', $lastDate)
            ->pluck('business_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        return $this->userActivityDates($user, $lastDate)
            ->reject(fn (string $date) => $closedDates->has($date))
            ->reject(fn (string $date) => $businessClosedDates->has($date))
            ->sortDesc()
            ->values()
            ->map(fn (string $date) => [
                'date' => $date,
                'label' => Carbon::parse($date)->format('d M Y'),
            ]);
    }

    /** @return Collection<int, string> */
    private function businessActivityDates(Carbon $throughDate): Collection
    {
        $saleDates = Sale::query()
            ->realSales()
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
            ->whereDate('sold_at', '<=', $throughDate)
            ->pluck('sold_at')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $paymentDates = SalePayment::query()
            ->whereDate('paid_at', '<=', $throughDate)
            ->whereHas('sale', fn ($query) => $query->realSales()->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED]))
            ->pluck('paid_at')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $expenseDates = DayExpense::query()
            ->whereDate('business_date', '<=', $throughDate)
            ->pluck('business_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        return $saleDates
            ->merge($paymentDates)
            ->merge($expenseDates)
            ->unique()
            ->values();
    }

    /** @return Collection<int, string> */
    private function userActivityDates(User $user, Carbon $throughDate): Collection
    {
        $saleDates = Sale::query()
            ->realSales()
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
            ->where('user_id', $user->id)
            ->whereDate('sold_at', '<=', $throughDate)
            ->pluck('sold_at')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $paymentDates = SalePayment::query()
            ->where('user_id', $user->id)
            ->whereDate('paid_at', '<=', $throughDate)
            ->whereHas('sale', fn ($query) => $query->realSales()->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED]))
            ->pluck('paid_at')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        $expenseDates = DayExpense::query()
            ->where('recorded_by_user_id', $user->id)
            ->whereDate('business_date', '<=', $throughDate)
            ->pluck('business_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        return $saleDates
            ->merge($paymentDates)
            ->merge($expenseDates)
            ->unique()
            ->values();
    }

    public function businessDayIsClosed(Carbon $businessDate): bool
    {
        return DayClose::query()->whereDate('business_date', $businessDate)->exists();
    }

    public function staffDayIsClosed(User $user, Carbon $businessDate): bool
    {
        return StaffDayClose::query()
            ->whereDate('business_date', $businessDate)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function addExpense(User $user, Carbon $businessDate, string $category, float $amount, ?string $description = null): DayExpense
    {
        if (! $user->canClosePersonalDay()) {
            abort(403, 'You do not have permission to record expenses.');
        }

        if ($businessDate->isFuture()) {
            throw ValidationException::withMessages([
                'business_date' => 'You cannot record expenses for a future date.',
            ]);
        }

        if ($this->businessDayIsClosed($businessDate)) {
            throw ValidationException::withMessages([
                'business_date' => 'This day is already closed by the owner. Expenses cannot be added.',
            ]);
        }

        if ($this->staffDayIsClosed($user, $businessDate)) {
            throw ValidationException::withMessages([
                'business_date' => 'You have already closed your day. Expenses cannot be added.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Expense amount must be greater than zero.',
            ]);
        }

        if (! array_key_exists($category, DayExpense::categoryOptions())) {
            throw ValidationException::withMessages([
                'category' => 'Please select a valid expense category.',
            ]);
        }

        return DayExpense::create([
            'business_date' => $businessDate->toDateString(),
            'category' => $category,
            'description' => $description,
            'amount' => round($amount, 2),
            'recorded_by_user_id' => $user->id,
        ])->load('recordedBy');
    }

    public function removeExpense(User $user, DayExpense $expense): void
    {
        if (! $user->canClosePersonalDay()) {
            abort(403, 'You do not have permission to remove expenses.');
        }

        if ($this->businessDayIsClosed($expense->business_date)) {
            throw ValidationException::withMessages([
                'expense' => 'This day is already closed by the owner. Expenses cannot be removed.',
            ]);
        }

        if ($this->staffDayIsClosed($user, $expense->business_date)) {
            throw ValidationException::withMessages([
                'expense' => 'You have already closed your day. Expenses cannot be removed.',
            ]);
        }

        if ($expense->recorded_by_user_id !== $user->id && ! $user->isOwner()) {
            abort(403, 'You can only remove your own expenses.');
        }

        $expense->delete();
    }

    public function userHasActivity(User $user, Carbon $businessDate): bool
    {
        $date = $businessDate->toDateString();

        $hasSales = Sale::query()
            ->realSales()
            ->whereDate('sold_at', $date)
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
            ->where('user_id', $user->id)
            ->exists();

        if ($hasSales) {
            return true;
        }

        $hasPayments = SalePayment::query()
            ->whereDate('paid_at', $date)
            ->where('user_id', $user->id)
            ->whereHas('sale', fn ($query) => $query->realSales()->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED]))
            ->exists();

        if ($hasPayments) {
            return true;
        }

        return DayExpense::query()
            ->whereDate('business_date', $date)
            ->where('recorded_by_user_id', $user->id)
            ->exists();
    }

    /** @return Collection<int, User> */
    public function pendingStaffForDate(Carbon $businessDate): Collection
    {
        $closedUserIds = StaffDayClose::query()
            ->whereDate('business_date', $businessDate)
            ->pluck('user_id');

        return $this->staffWithActivity($businessDate)
            ->reject(fn (User $staff) => $closedUserIds->contains($staff->id));
    }

    public function ownerCanCloseBusinessDay(User $owner, Carbon $businessDate): bool
    {
        if ($this->businessDayIsClosed($businessDate)) {
            return false;
        }

        if ($this->pendingStaffForDate($businessDate)->isNotEmpty()) {
            return false;
        }

        if ($this->userHasActivity($owner, $businessDate) && ! $this->staffDayIsClosed($owner, $businessDate)) {
            return false;
        }

        return true;
    }

    /** @param  Collection<int, StaffDayClose>  $staffCloses */
    public function aggregateStaffCloseSummaries(Collection $staffCloses): array
    {
        $summary = [
            'sales_count' => 0,
            'sales_total' => 0.0,
            'payments_count' => 0,
            'cash_collected' => 0.0,
            'mobile_collected' => 0.0,
            'mobile_providers' => [],
            'bank_collected' => 0.0,
            'bank_providers' => [],
            'credit_recorded' => 0.0,
            'total_collected' => 0.0,
            'expenses_count' => 0,
            'expenses_total' => 0.0,
            'expenses_by_category' => [],
            'expenses' => [],
            'net_in_hand' => 0.0,
            'pending_sales_count' => 0,
            'pending_sales_total' => 0.0,
            'paid_sales_count' => 0,
        ];

        if ($staffCloses->isEmpty()) {
            return $summary;
        }

        $mobileProviders = [];
        $bankProviders = [];
        $expensesByCategory = [];

        foreach ($staffCloses as $close) {
            $row = $close->summary ?? [];

            $summary['sales_count'] += (int) ($row['sales_count'] ?? 0);
            $summary['sales_total'] += (float) ($row['sales_total'] ?? 0);
            $summary['payments_count'] += (int) ($row['payments_count'] ?? 0);
            $summary['cash_collected'] += (float) ($row['cash_collected'] ?? 0);
            $summary['mobile_collected'] += (float) ($row['mobile_collected'] ?? 0);
            $summary['bank_collected'] += (float) ($row['bank_collected'] ?? 0);
            $summary['credit_recorded'] += (float) ($row['credit_recorded'] ?? 0);
            $summary['total_collected'] += (float) ($row['total_collected'] ?? 0);
            $summary['expenses_count'] += (int) ($row['expenses_count'] ?? 0);
            $summary['expenses_total'] += (float) ($row['expenses_total'] ?? 0);
            $summary['net_in_hand'] += (float) ($row['net_in_hand'] ?? 0);
            $summary['pending_sales_count'] += (int) ($row['pending_sales_count'] ?? 0);
            $summary['pending_sales_total'] += (float) ($row['pending_sales_total'] ?? 0);
            $summary['paid_sales_count'] += (int) ($row['paid_sales_count'] ?? 0);

            foreach ($row['mobile_providers'] ?? [] as $provider) {
                $key = $provider['name'] ?? 'unknown';
                if (! isset($mobileProviders[$key])) {
                    $mobileProviders[$key] = ['name' => $key, 'amount' => 0.0, 'count' => 0];
                }
                $mobileProviders[$key]['amount'] += (float) ($provider['amount'] ?? 0);
                $mobileProviders[$key]['count'] += (int) ($provider['count'] ?? 0);
            }

            foreach ($row['bank_providers'] ?? [] as $provider) {
                $key = $provider['name'] ?? 'unknown';
                if (! isset($bankProviders[$key])) {
                    $bankProviders[$key] = ['name' => $key, 'amount' => 0.0, 'count' => 0];
                }
                $bankProviders[$key]['amount'] += (float) ($provider['amount'] ?? 0);
                $bankProviders[$key]['count'] += (int) ($provider['count'] ?? 0);
            }

            foreach ($row['expenses_by_category'] ?? [] as $category) {
                $key = $category['category'] ?? $category['label'] ?? 'other';
                if (! isset($expensesByCategory[$key])) {
                    $expensesByCategory[$key] = [
                        'category' => $key,
                        'label' => $category['label'] ?? ucfirst($key),
                        'amount' => 0.0,
                        'count' => 0,
                    ];
                }
                $expensesByCategory[$key]['amount'] += (float) ($category['amount'] ?? 0);
                $expensesByCategory[$key]['count'] += (int) ($category['count'] ?? 0);
            }

            foreach ($row['expenses'] ?? [] as $expense) {
                $summary['expenses'][] = $expense;
            }
        }

        $summary['sales_total'] = round($summary['sales_total'], 2);
        $summary['cash_collected'] = round($summary['cash_collected'], 2);
        $summary['mobile_collected'] = round($summary['mobile_collected'], 2);
        $summary['bank_collected'] = round($summary['bank_collected'], 2);
        $summary['credit_recorded'] = round($summary['credit_recorded'], 2);
        $summary['total_collected'] = round($summary['total_collected'], 2);
        $summary['expenses_total'] = round($summary['expenses_total'], 2);
        $summary['net_in_hand'] = round($summary['net_in_hand'], 2);
        $summary['pending_sales_total'] = round($summary['pending_sales_total'], 2);
        $summary['mobile_providers'] = collect($mobileProviders)->sortByDesc('amount')->values()->all();
        $summary['bank_providers'] = collect($bankProviders)->sortByDesc('amount')->values()->all();
        $summary['expenses_by_category'] = collect($expensesByCategory)->sortByDesc('amount')->values()->all();

        return $summary;
    }

    public function closeStaffDay(User $user, Carbon $businessDate, ?string $notes = null): StaffDayClose
    {
        if (! $user->canClosePersonalDay()) {
            abort(403, 'You do not have permission to close your day.');
        }

        if ($businessDate->isFuture()) {
            throw ValidationException::withMessages([
                'business_date' => 'You cannot close a future date.',
            ]);
        }

        if ($this->businessDayIsClosed($businessDate)) {
            throw ValidationException::withMessages([
                'business_date' => 'The owner has already closed this business day.',
            ]);
        }

        if ($this->staffDayIsClosed($user, $businessDate)) {
            throw ValidationException::withMessages([
                'business_date' => 'You have already closed your day for this date.',
            ]);
        }

        $summary = $this->buildSummary($businessDate, $user);

        return DB::transaction(function () use ($user, $businessDate, $notes, $summary) {
            return StaffDayClose::create([
                'business_date' => $businessDate->toDateString(),
                'user_id' => $user->id,
                'closed_at' => now(),
                'notes' => $notes,
                'summary' => $summary,
            ])->load('user');
        });
    }

    public function closeBusinessDay(User $user, Carbon $businessDate, ?string $notes = null): DayClose
    {
        if (! $user->canCloseBusinessDay()) {
            abort(403, 'Only the owner can close the business day.');
        }

        if ($businessDate->isFuture()) {
            throw ValidationException::withMessages([
                'business_date' => 'You cannot close a future date.',
            ]);
        }

        if ($this->businessDayIsClosed($businessDate)) {
            throw ValidationException::withMessages([
                'business_date' => 'This business day has already been closed.',
            ]);
        }

        $pendingStaff = $this->pendingStaffForDate($businessDate);
        if ($pendingStaff->isNotEmpty()) {
            throw ValidationException::withMessages([
                'business_date' => 'Waiting for '.$pendingStaff->pluck('name')->join(', ').' to close their day first.',
            ]);
        }

        if ($this->userHasActivity($user, $businessDate) && ! $this->staffDayIsClosed($user, $businessDate)) {
            throw ValidationException::withMessages([
                'business_date' => 'Close your personal day first before closing the business day.',
            ]);
        }

        $staffCloses = StaffDayClose::query()
            ->with('user:id,name')
            ->whereDate('business_date', $businessDate)
            ->orderBy('closed_at')
            ->get();

        $summary = $this->aggregateStaffCloseSummaries($staffCloses);
        $summary['staff_closes'] = $staffCloses->map(fn (StaffDayClose $close) => [
            'user_id' => $close->user_id,
            'user_name' => $close->user?->name,
            'closed_at' => $close->closed_at->toIso8601String(),
            'total_collected' => $close->summaryValue('total_collected'),
            'expenses_total' => $close->summaryValue('expenses_total'),
            'net_in_hand' => $close->summaryValue('net_in_hand'),
            'notes' => $close->notes,
        ])->values()->all();

        $summary['staff_closes_count'] = $staffCloses->count();

        return DB::transaction(function () use ($user, $businessDate, $notes, $summary) {
            return DayClose::create([
                'business_date' => $businessDate->toDateString(),
                'closed_by_user_id' => $user->id,
                'closed_at' => now(),
                'notes' => $notes,
                'summary' => $summary,
            ])->load('closedBy');
        });
    }
}
