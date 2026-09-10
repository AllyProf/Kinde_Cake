<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\Item;
use App\Models\PaymentProvider;
use App\Models\Sale;
use App\Models\User;
use App\Services\CakePointService;
use App\Services\SaleService;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private SaleService $sales,
        private SmsService $sms,
        private CakePointService $cakePoint,
    ) {}

    public function index(Request $request): View
    {
        $filters = array_merge([
            'search' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
        ], $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in([
                Sale::STATUS_PENDING,
                Sale::STATUS_PARTIAL,
                Sale::STATUS_PAID,
                Sale::STATUS_CANCELLED,
                Sale::STATUS_DELETED,
            ])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]));

        $query = Sale::query()
            ->realSales();

        if (! $request->user()->isOwner() && ! $request->user()->canPaySales()) {
            $query->visibleTo($request->user());
        }

        $query->with(['user', 'items.item', 'paymentProvider', 'payments'])
            ->latest('sold_at')
            ->latest('id');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($builder) use ($search) {
                $builder->where('sale_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhereHas('items.item', fn ($itemQuery) => $itemQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('sold_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('sold_at', '<=', $filters['date_to']);
        }

        $sales = $query->paginate(10)->withQueryString();
        $paymentProviderOptions = $this->paymentProviderOptions();
        $payCustomers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('sales.index', compact('sales', 'paymentProviderOptions', 'filters', 'payCustomers'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $data = $this->formData();

        if ($request->filled('from')) {
            $cakePointSale = Sale::query()
                ->with(['items.item'])
                ->find($request->integer('from'));

            if (! $cakePointSale || ! $cakePointSale->canConvertToSale($request->user())) {
                return redirect()
                    ->route('cake-point.index')
                    ->with('error', 'This cake point order cannot be opened for sale creation.');
            }

            $data['cakePointSale'] = $cakePointSale;
            $data['saleFormInitial'] = [
                'customer_id' => $cakePointSale->customer_id,
                'customer_name' => $cakePointSale->customer_name,
                'customer_phone' => $cakePointSale->customer_phone,
                'notes' => $cakePointSale->notes,
                'items' => $cakePointSale->items->map(fn ($line) => [
                    'item_id' => $line->item_id,
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                    'discount' => (float) $line->discount,
                ])->values()->all(),
                'ingredients' => [],
            ];
        }

        $data['saleFormInitial'] = $data['saleFormInitial'] ?? ['items' => [], 'ingredients' => []];

        return view('sales.create', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSalePayload($request);

        $sale = $this->sales->create(
            $request->user(),
            collect($validated['items'])->values()->all(),
            collect($validated['ingredients'])->values()->all(),
            isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
            $validated['customer_name'] ?? null,
            $validated['customer_phone'] ?? null,
            $validated['notes'] ?? null,
            $validated['sold_at'],
        );

        if (! empty($validated['cake_point_sale_id'])) {
            $cakePointSale = Sale::query()->find($validated['cake_point_sale_id']);

            if ($cakePointSale && $cakePointSale->canConvertToSale($request->user())) {
                $this->cakePoint->completeFromSale($cakePointSale, $sale);
            }
        }

        return redirect()
            ->route('sales.index')
            ->with('success', "Sale {$sale->sale_number} recorded. You can collect payment from the sales list.");
    }

    public function show(Sale $sale): View
    {
        $this->authorizeSaleAccess($sale);

        $sale->load([
            'user',
            'assignedTo',
            'assignedBy',
            'paymentProvider',
            'payments.user',
            'payments.paymentProvider',
            'items.item.packageUnit',
            'ingredientUsages.ingredient.usagePackageUnit',
        ]);

        $paymentProviderOptions = $this->paymentProviderOptions();
        $payCustomers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $cakePointStaff = auth()->user()->isOwner()
            ? User::query()->where('role', User::ROLE_STAFF)->where('is_active', true)->orderBy('name')->get()
            : collect();
        $smsReady = $this->sms->isReady();

        return view('sales.show', compact(
            'sale',
            'paymentProviderOptions',
            'payCustomers',
            'cakePointStaff',
            'smsReady',
        ));
    }

    public function edit(Sale $sale): View|RedirectResponse
    {
        $this->authorizeSaleAccess($sale);

        if (! $sale->canBeEdited()) {
            return redirect()
                ->route('sales.index')
                ->with('error', 'Only pending sales can be edited.');
        }

        $sale->load(['items', 'ingredientUsages']);

        $data = $this->formData();
        $usageByIngredient = $sale->ingredientUsages
            ->groupBy('ingredient_id')
            ->map(fn ($rows) => $rows->sum('quantity_used'));

        $data['ingredientOptions'] = collect($data['ingredientOptions'])
            ->map(function (array $option) use ($usageByIngredient) {
                $option['stock'] = (float) $option['stock'] + (float) ($usageByIngredient[$option['id']] ?? 0);

                return $option;
            })
            ->values();

        return view('sales.edit', array_merge($data, [
            'sale' => $sale,
            'initialItems' => $sale->items->map(fn ($line) => [
                'item_id' => $line->item_id,
                'quantity' => (float) $line->quantity,
                'unit_price' => (float) $line->unit_price,
                'discount' => (float) $line->discount,
            ])->values(),
            'initialIngredients' => $sale->ingredientUsages->map(fn ($usage) => [
                'ingredient_id' => $usage->ingredient_id,
                'quantity_used' => (float) $usage->quantity_used,
            ])->values(),
        ]));
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorizeSaleAccess($sale);

        $validated = $this->validateSalePayload($request);

        $this->sales->update(
            $sale,
            collect($validated['items'])->values()->all(),
            collect($validated['ingredients'])->values()->all(),
            isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
            $validated['customer_name'] ?? null,
            $validated['customer_phone'] ?? null,
            $validated['notes'] ?? null,
            $validated['sold_at'],
        );

        return redirect()
            ->route('sales.index')
            ->with('success', "Sale {$sale->sale_number} updated successfully.");
    }

    public function pay(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorizeSalePayment($sale);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'payment_method' => ['required', Rule::in(array_keys(Sale::paymentMethodOptions()))],
            'payment_provider_id' => ['nullable', 'exists:payment_providers,id'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'credit_repayment_date' => ['nullable', 'date'],
        ]);

        if ($validated['payment_method'] === Sale::PAYMENT_CREDIT
            || (float) $validated['amount'] < $sale->balanceDue()) {
            $request->validate([
                'credit_repayment_date' => ['required', 'date', 'after_or_equal:today'],
                'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
                'customer_id' => ['required_without:customer_name', 'nullable', 'exists:customers,id'],
            ]);
        }

        if (! empty($validated['customer_id'])) {
            $customer = Customer::query()->find($validated['customer_id']);
            if ($customer) {
                $validated['customer_name'] = $customer->name;
                $validated['customer_phone'] = $customer->phone;
            }
        }

        $this->sales->pay(
            $request->user(),
            $sale,
            (float) $validated['amount'],
            $validated['payment_method'],
            isset($validated['payment_provider_id']) ? (int) $validated['payment_provider_id'] : null,
            $validated['payment_reference'] ?? null,
            isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
            $validated['customer_name'] ?? null,
            $validated['customer_phone'] ?? null,
            $validated['credit_repayment_date'] ?? null,
        );

        return redirect()
            ->back()
            ->with('success', $this->paymentSuccessMessage($sale->fresh()));
    }

    private function paymentSuccessMessage(Sale $sale): string
    {
        if ($sale->isPaid()) {
            return "Payment completed for {$sale->sale_number}.";
        }

        if ($sale->isCreditPayment() && $sale->hasOutstandingBalance()) {
            return "Credit recorded for {$sale->sale_number}. Balance due: {$sale->formattedBalanceDue()}. Track it in Debt Management.";
        }

        return "Partial payment recorded for {$sale->sale_number}. Balance: {$sale->formattedBalanceDue()}.";
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->authorizeSaleAccess($sale);

        $saleNumber = $sale->sale_number;
        $this->sales->destroy($sale);

        return redirect()
            ->route('sales.index')
            ->with('success', "Sale {$saleNumber} marked as deleted. Stock restored and the record remains in the list.");
    }

    /** @return array<string, mixed> */
    private function validateSalePayload(Request $request): array
    {
        return $request->validate([
            'cake_point_sale_id' => ['nullable', 'exists:sales,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'sold_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.9999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.ingredient_id' => ['required', 'exists:ingredients,id'],
            'ingredients.*.quantity_used' => ['required', 'numeric', 'gt:0', 'max:999999999.9999'],
        ]);
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        $items = Item::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $ingredients = Ingredient::query()
            ->with('usagePackageUnit')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'items' => $items,
            'ingredients' => $ingredients,
            'customers' => $customers,
            'itemOptions' => $items->map(fn (Item $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => (float) $item->price,
                'unit' => $item->packageUnit?->symbol,
            ])->values(),
            'ingredientOptions' => $ingredients->map(fn (Ingredient $ingredient) => [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'unit' => $ingredient->usagePackageUnit?->symbol,
                'stock' => (float) $ingredient->stock_quantity,
            ])->values(),
            'customerOptions' => $customers->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'label' => $customer->displayLabel(),
            ])->values(),
        ];
    }

    /** @return \Illuminate\Support\Collection<int, array{id: int, name: string, type: string}> */
    private function paymentProviderOptions()
    {
        return PaymentProvider::query()
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
    }

    private function authorizeSaleAccess(Sale $sale): void
    {
        $user = auth()->user();

        if ($user->isOwner() || $user->canPaySales() || $sale->isVisibleTo($user)) {
            return;
        }

        abort(403, 'You can only access your own sales.');
    }

    private function authorizeSalePayment(Sale $sale): void
    {
        if ($sale->isCakePointWorkOrder()) {
            abort(403, 'Cake point work orders must be completed as a sale first.');
        }

        if (! auth()->user()->canPaySales()) {
            abort(403, 'You do not have permission to record payments.');
        }
    }
}
