<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PaymentProvider;
use App\Models\Sale;
use App\Models\SmsMessage;
use App\Services\SmsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    public function __construct(private SmsService $sms) {}

    public function index(Request $request): View
    {
        $filters = array_merge([
            'search' => '',
            'period' => 'all',
            'payment' => '',
            'collect_from' => '',
            'collect_to' => '',
        ], $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'period' => ['nullable', Rule::in(['all', 'today', 'week', 'month', 'upcoming', 'overdue'])],
            'payment' => ['nullable', Rule::in(['', 'unpaid', 'partial', 'paid'])],
            'collect_from' => ['nullable', 'date'],
            'collect_to' => ['nullable', 'date', 'after_or_equal:collect_from'],
        ]));

        $query = $this->baseOrdersQuery($request)
            ->with(['user', 'assignedBy', 'assignedTo', 'items.item', 'paymentProvider', 'payments'])
            ->orderByRaw('CASE WHEN ready_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('ready_at')
            ->latest('sold_at')
            ->latest('id');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('sale_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($itemQuery) => $itemQuery->where('item_name', 'like', "%{$search}%"))
                    ->orWhereHas('items.item', fn ($itemQuery) => $itemQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('assignedBy', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
            });
        }

        $this->applyCollectionPeriod($query, $filters['period'] ?: 'all');

        if (! empty($filters['collect_from'])) {
            $query->whereDate('ready_at', '>=', $filters['collect_from']);
        }

        if (! empty($filters['collect_to'])) {
            $query->whereDate('ready_at', '<=', $filters['collect_to']);
        }

        $this->applyPaymentFilter($query, $filters['payment'] ?? '');

        $orders = $query->paginate(20)->withQueryString();

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

        $summaryBase = $this->baseOrdersQuery($request);

        $summary = [
            'total' => (clone $summaryBase)->count(),
            'unpaid' => (clone $summaryBase)->where(function (Builder $builder) {
                $builder->whereColumn('amount_paid', '<', 'total')
                    ->orWhereNull('amount_paid');
            })->count(),
            'collect_today' => (clone $summaryBase)->whereDate('ready_at', today())->count(),
            'upcoming' => (clone $summaryBase)->whereNotNull('ready_at')->where('ready_at', '>=', now())->count(),
        ];

        $smsReady = $this->sms->isReady();

        return view('customer-orders.index', compact(
            'orders',
            'filters',
            'paymentProviderOptions',
            'payCustomers',
            'summary',
            'smsReady',
        ));
    }

    public function notifyReady(Request $request, Sale $sale): RedirectResponse
    {
        if ($sale->isCakePointWorkOrder() || $sale->isVoided()) {
            return redirect()
                ->back()
                ->with('error', 'This order cannot receive a ready SMS.');
        }

        if (! $sale->isVisibleTo($request->user()) && ! $request->user()->isOwner() && ! $request->user()->canPaySales()) {
            abort(403);
        }

        if (! filled($sale->customer_phone)) {
            return redirect()
                ->back()
                ->with('error', 'This order has no customer phone number.');
        }

        if (! $this->sms->isReady()) {
            return redirect()
                ->back()
                ->with('error', 'SMS is not enabled or configured. Set it up in Settings → SMS.');
        }

        $phone = $this->sms->normalizePhone($sale->customer_phone);

        if (! $phone) {
            return redirect()
                ->back()
                ->with('error', 'The customer phone number is invalid.');
        }

        $sale->loadMissing(['items.item']);
        $body = $this->sms->buildCakeReadyMessage($sale);

        $smsMessage = SmsMessage::create([
            'created_by_user_id' => $request->user()->id,
            'recipient_phone' => $phone,
            'recipient_name' => $sale->customer_name,
            'recipient_type' => SmsMessage::RECIPIENT_CUSTOMER,
            'recipient_id' => $sale->customer_id,
            'type' => SmsMessage::TYPE_CAKE_READY,
            'message' => $body,
            'status' => SmsMessage::STATUS_SENDING,
            'attempts' => 1,
            'related_type' => Sale::class,
            'related_id' => $sale->id,
        ]);

        try {
            $response = $this->sms->send($phone, $body);
            $smsMessage->update([
                'status' => SmsMessage::STATUS_SENT,
                'sent_at' => now(),
                'provider' => $this->sms->currentDriver(),
                'provider_response' => $response,
                'last_error' => null,
            ]);
            $sale->update(['ready_sms_sent_at' => now()]);

            return redirect()
                ->back()
                ->with('success', "Ready SMS sent to {$sale->customer_name} ({$phone}).");
        } catch (\Throwable $exception) {
            $smsMessage->update([
                'status' => SmsMessage::STATUS_FAILED,
                'last_error' => $exception->getMessage(),
            ]);

            Log::warning('Cake ready SMS failed', [
                'sale_id' => $sale->id,
                'error' => $exception->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'Ready SMS failed: '.$exception->getMessage());
        }
    }

    private function baseOrdersQuery(Request $request): Builder
    {
        $query = Sale::query()
            ->where(function (Builder $builder) {
                $builder->where(function (Builder $realSales) {
                    $realSales->whereNull('cake_point_status')
                        ->whereNotNull('customer_name')
                        ->where('customer_name', '!=', '');
                })->orWhere(function (Builder $cakePointOrders) {
                    $cakePointOrders->whereIn('cake_point_status', [
                        Sale::CAKE_POINT_RECEIVED,
                        Sale::CAKE_POINT_PREPARED,
                    ])
                        ->whereNotNull('customer_name')
                        ->where('customer_name', '!=', '');
                });
            })
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED]);

        if (! $request->user()->isOwner() && ! $request->user()->canPaySales()) {
            $query->visibleTo($request->user());
        }

        return $query;
    }

    private function applyCollectionPeriod(Builder $query, string $period): void
    {
        $now = now();

        match ($period) {
            'today' => $query->whereDate('ready_at', $now->toDateString()),
            'week' => $query->whereBetween('ready_at', [
                $now->copy()->startOfWeek()->startOfDay(),
                $now->copy()->endOfWeek()->endOfDay(),
            ]),
            'month' => $query->whereBetween('ready_at', [
                $now->copy()->startOfMonth()->startOfDay(),
                $now->copy()->endOfMonth()->endOfDay(),
            ]),
            'upcoming' => $query->whereNotNull('ready_at')->where('ready_at', '>=', $now),
            'overdue' => $query->whereNotNull('ready_at')
                ->where('ready_at', '<', $now)
                ->where(function (Builder $builder) {
                    $builder->whereColumn('amount_paid', '<', 'total')
                        ->orWhereNull('amount_paid');
                }),
            default => null,
        };
    }

    private function applyPaymentFilter(Builder $query, string $payment): void
    {
        if ($payment === 'unpaid') {
            $query->where(function (Builder $builder) {
                $builder->whereNull('amount_paid')
                    ->orWhere('amount_paid', '<=', 0);
            })->where('status', '!=', Sale::STATUS_PAID);

            return;
        }

        if ($payment === 'partial') {
            $query->whereColumn('amount_paid', '>', 0)
                ->whereColumn('amount_paid', '<', 'total');

            return;
        }

        if ($payment === 'paid') {
            $query->where('status', Sale::STATUS_PAID);
        }
    }
}
