<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Item;
use App\Models\PaymentProvider;
use App\Models\Sale;
use App\Models\User;
use App\Services\CakePointService;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CakePointController extends Controller
{
    public function __construct(
        private CakePointService $cakePoint,
        private SmsService $sms,
    ) {}

    public function index(Request $request): View
    {
        $filters = array_merge([
            'view' => auth()->user()->isOwner() ? 'assigned' : 'mine',
            'search' => '',
            'date_from' => '',
            'date_to' => '',
        ], $request->validate([
            'view' => ['nullable', Rule::in(['assigned', 'mine', 'all'])],
            'search' => ['nullable', 'string', 'max:120'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]));

        if (! auth()->user()->isOwner()) {
            $filters['view'] = 'mine';
        }

        $query = Sale::query()
            ->visibleTo($request->user())
            ->with(['user', 'assignedTo', 'assignedBy', 'items.item.packageUnit', 'convertedToSale.paymentProvider', 'convertedToSale.payments.user'])
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
            ->latest('sold_at')
            ->latest('id');

        if ($filters['view'] === 'assigned') {
            $query->whereNotNull('assigned_to_user_id')
                ->whereNotNull('cake_point_status');
        } elseif ($filters['view'] === 'mine') {
            $query->where('assigned_to_user_id', $request->user()->id)
                ->whereNotNull('cake_point_status');
        } elseif ($filters['view'] === 'all') {
            $query->whereNotNull('cake_point_status');
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('sale_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhereHas('items.item', fn ($itemQuery) => $itemQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('assignedTo', fn ($staffQuery) => $staffQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('sold_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('sold_at', '<=', $filters['date_to']);
        }

        $orders = $query->paginate(15)->withQueryString();
        $staffMembers = auth()->user()->isOwner()
            ? User::query()->where('role', User::ROLE_STAFF)->where('is_active', true)->orderBy('name')->get()
            : collect();

        $items = auth()->user()->isOwner()
            ? Item::query()->with('packageUnit')->where('is_active', true)->orderBy('name')->get()
            : collect();

        $customers = auth()->user()->isOwner()
            ? Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone', 'birthday'])
            : collect();

        $smsReady = $this->sms->isReady();

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

        return view('cake-point.index', compact(
            'orders',
            'filters',
            'staffMembers',
            'items',
            'customers',
            'smsReady',
            'paymentProviderOptions',
            'payCustomers',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        if (! auth()->user()->isOwner()) {
            abort(403, 'Only the owner can send orders to the cake point.');
        }

        $validated = $request->validate([
            'staff_id' => ['required', 'exists:users,id'],
            'item_id' => ['required', 'exists:items,id'],
            'item_name' => ['nullable', 'string', 'max:255'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:9999'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_birthday' => ['nullable', 'date'],
            'ready_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'send_sms' => ['nullable', 'boolean'],
            'advance_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'payment_method' => ['nullable', Rule::in(array_keys(Sale::paymentMethodOptions()))],
            'payment_provider_id' => ['nullable', 'exists:payment_providers,id'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);

        $item = Item::query()->where('is_active', true)->findOrFail($validated['item_id']);

        if ($item->is_custom) {
            if (! filled($validated['item_name'] ?? null)) {
                return redirect()
                    ->route('cake-point.index')
                    ->withInput()
                    ->with('error', 'Enter the custom cake name.');
            }
            if ((float) ($validated['unit_price'] ?? 0) <= 0) {
                return redirect()
                    ->route('cake-point.index')
                    ->withInput()
                    ->with('error', 'Enter a price for the custom cake.');
            }
        }

        $staff = User::query()
            ->where('role', User::ROLE_STAFF)
            ->where('is_active', true)
            ->findOrFail($validated['staff_id']);

        $sale = null;
        $advanceAmount = (float) ($validated['advance_amount'] ?? 0);

        if ($advanceAmount > 0 && empty($validated['payment_method'])) {
            return redirect()
                ->route('cake-point.index')
                ->withInput()
                ->with('error', 'Choose a payment method for the advance payment.');
        }

        $line = [
            'item_id' => (int) $validated['item_id'],
            'quantity' => (float) $validated['quantity'],
            'discount' => (float) ($validated['discount'] ?? 0),
        ];

        if ($item->is_custom) {
            $line['item_name'] = trim((string) $validated['item_name']);
            $line['unit_price'] = (float) $validated['unit_price'];
        }

        try {
            $sale = $this->cakePoint->createAndAssign(
                $request->user(),
                $staff,
                [$line],
                isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
                $validated['customer_name'] ?? null,
                $validated['customer_phone'],
                $validated['notes'] ?? null,
                $request->boolean('send_sms'),
                [
                    'ready_at' => $validated['ready_at'] ?? null,
                    'customer_birthday' => $validated['customer_birthday'] ?? null,
                    'advance_amount' => $advanceAmount,
                    'payment_method' => $validated['payment_method'] ?? null,
                    'payment_provider_id' => isset($validated['payment_provider_id'])
                        ? (int) $validated['payment_provider_id']
                        : null,
                    'payment_reference' => $validated['payment_reference'] ?? null,
                ],
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $errors = $exception->errors();

            if (isset($errors['sms']) && isset($sale)) {
                return redirect()
                    ->route('cake-point.index', ['view' => 'assigned'])
                    ->with('warning', "Order {$sale->sale_number} sent to {$staff->name}, but SMS failed: ".collect($errors['sms'])->first());
            }

            return redirect()
                ->route('cake-point.index')
                ->withInput()
                ->with('error', collect($errors)->flatten()->first());
        }

        $message = "Order {$sale->sale_number} sent to {$staff->name}.";
        if ($sale->ready_at) {
            $message .= ' Ready '.$sale->ready_at->format('d M Y H:i').'.';
        }
        if ($advanceAmount > 0) {
            $message .= ' Advance '.number_format($advanceAmount, 0).' TZS recorded.';
        }
        if ($request->boolean('send_sms')) {
            $notices = [];
            if (filled($staff->phone)) {
                $notices[] = 'staff';
            }
            if (filled($sale->customer_phone)) {
                $notices[] = 'customer';
            }
            if ($notices !== []) {
                $message .= ' SMS sent to '.implode(' & ', $notices).'.';
            }
        }

        return redirect()
            ->route('cake-point.index', ['view' => 'assigned'])
            ->with('success', $message);
    }

    public function assign(Request $request, Sale $sale): RedirectResponse
    {
        if (! auth()->user()->isOwner()) {
            abort(403, 'Only the owner can send orders to the cake point.');
        }

        $validated = $request->validate([
            'staff_id' => ['required', 'exists:users,id'],
            'send_sms' => ['nullable', 'boolean'],
        ]);

        $staff = User::query()
            ->where('role', User::ROLE_STAFF)
            ->where('is_active', true)
            ->findOrFail($validated['staff_id']);

        try {
            $this->cakePoint->assign(
                $sale,
                $request->user(),
                $staff,
                $request->boolean('send_sms'),
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $errors = $exception->errors();

            if (isset($errors['sms'])) {
                return redirect()
                    ->back()
                    ->with('warning', "Order {$sale->sale_number} assigned to {$staff->name}, but SMS failed: ".collect($errors['sms'])->first());
            }

            return redirect()
                ->back()
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        $message = "Order {$sale->sale_number} sent to {$staff->name}.";
        if ($request->boolean('send_sms') && filled($staff->phone)) {
            $message .= ' Staff notified by SMS.';
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    public function updateStatus(Request $request, Sale $sale): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Sale::CAKE_POINT_RECEIVED,
                Sale::CAKE_POINT_PREPARED,
            ])],
        ]);

        try {
            $updated = $this->cakePoint->updateStatus($sale, $request->user(), $validated['status']);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()
                ->back()
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        $label = $updated->cakePointStatusLabel();

        if ($validated['status'] === Sale::CAKE_POINT_RECEIVED) {
            return redirect()
                ->route('customer-orders.index')
                ->with('success', "Order {$updated->sale_number} received. It is now on Customer Orders.");
        }

        return redirect()
            ->back()
            ->with('success', "Order {$updated->sale_number} marked as {$label}.");
    }
}
