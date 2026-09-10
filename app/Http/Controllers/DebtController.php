<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PaymentProvider;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DebtController extends Controller
{
    public function index(Request $request): View
    {
        $filters = array_merge([
            'search' => '',
            'type' => '',
            'due' => '',
        ], $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['partial', 'credit', 'unpaid'])],
            'due' => ['nullable', Rule::in(['overdue', 'upcoming', 'none'])],
        ]));

        $query = $this->filteredDebtQuery($filters, $request->user())
            ->with(['user', 'paymentProvider'])
            ->orderByRaw('credit_repayment_date IS NULL')
            ->orderBy('credit_repayment_date')
            ->latest('sold_at')
            ->latest('id');

        $debts = $query->paginate(15)->withQueryString();

        $summaryQuery = $this->filteredDebtQuery($filters, $request->user());
        $summaryRows = (clone $summaryQuery)->get(['id', 'total', 'amount_paid', 'payment_method', 'credit_repayment_date', 'status']);

        $stats = [
            'count' => $summaryRows->count(),
            'total_outstanding' => $summaryRows->sum(fn (Sale $sale) => $sale->debtAmount()),
            'overdue_count' => $summaryRows->filter(fn (Sale $sale) => $sale->isDebtOverdue())->count(),
        ];

        $paymentProviderOptions = PaymentProvider::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (PaymentProvider $provider) => [
                'id' => $provider->id,
                'name' => $provider->name,
                'type' => $provider->type,
            ])
            ->values();

        $payCustomers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('debts.index', compact('debts', 'filters', 'stats', 'paymentProviderOptions', 'payCustomers'));
    }

    /** @param  array<string, string>  $filters */
    private function filteredDebtQuery(array $filters, User $user)
    {
        $query = Sale::query()->withOpenDebt();

        if (! $user->isOwner() && ! $user->canPaySales()) {
            $query->visibleTo($user);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('sale_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($filters['type'] === 'partial') {
            $query->where('status', Sale::STATUS_PARTIAL);
        } elseif ($filters['type'] === 'credit') {
            $query->where('payment_method', Sale::PAYMENT_CREDIT);
        } elseif ($filters['type'] === 'unpaid') {
            $query->where('status', Sale::STATUS_PENDING)
                ->where('amount_paid', '<=', 0);
        }

        if ($filters['due'] === 'overdue') {
            $query->whereNotNull('credit_repayment_date')
                ->whereDate('credit_repayment_date', '<', now()->toDateString());
        } elseif ($filters['due'] === 'upcoming') {
            $query->whereNotNull('credit_repayment_date')
                ->whereDate('credit_repayment_date', '>=', now()->toDateString());
        } elseif ($filters['due'] === 'none') {
            $query->whereNull('credit_repayment_date');
        }

        return $query;
    }
}
