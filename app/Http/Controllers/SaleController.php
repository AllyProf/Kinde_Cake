<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\Item;
use App\Models\PaymentProvider;
use App\Models\Sale;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\AppSettingsService;
use App\Services\CakePointService;
use App\Services\SaleService;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private SaleService $sales,
        private SmsService $sms,
        private CakePointService $cakePoint,
        private AppSettingsService $settings,
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
                ->with(['items.item', 'customer'])
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
                'ready_at' => $cakePointSale->ready_at?->format('Y-m-d\TH:i'),
                'customer_birthday' => $cakePointSale->customer?->birthday?->format('Y-m-d'),
                'existing_advance' => (float) $cakePointSale->amount_paid,
                'items' => $cakePointSale->items->map(fn ($line) => [
                    'item_id' => $line->item_id,
                    'item_name' => $line->item_name,
                    'quantity' => (float) $line->quantity,
                    'unit_price' => (float) $line->unit_price,
                    'discount' => (float) $line->discount,
                ])->values()->all(),
                'ingredients' => [],
            ];
        }

        $data['saleFormInitial'] = $data['saleFormInitial'] ?? ['items' => [], 'ingredients' => []];
        $data['paymentProviderOptions'] = $this->paymentProviderOptions();
        $data['smsReady'] = $this->sms->isReady();

        return view('sales.create', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSalePayload($request);
        $advanceAmount = (float) ($validated['advance_amount'] ?? 0);

        if ($advanceAmount > 0 && empty($validated['payment_method'])) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Choose a payment method for the amount paid now.');
        }

        $customerId = isset($validated['customer_id']) ? (int) $validated['customer_id'] : null;
        $customerId = $this->syncCustomerBirthday(
            $customerId,
            $validated['customer_name'] ?? null,
            $validated['customer_phone'] ?? null,
            $validated['customer_birthday'] ?? null,
        );

        $sale = $this->sales->create(
            $request->user(),
            collect($validated['items'])->values()->all(),
            $validated['ingredients'],
            $customerId,
            $validated['customer_name'] ?? null,
            $validated['customer_phone'] ?? null,
            $validated['notes'] ?? null,
            $validated['sold_at'],
            $validated['ready_at'] ?? null,
        );

        if (! empty($validated['cake_point_sale_id'])) {
            $cakePointSale = Sale::query()->find($validated['cake_point_sale_id']);

            if ($cakePointSale && $cakePointSale->canConvertToSale($request->user())) {
                if ((float) $cakePointSale->amount_paid > 0) {
                    $this->sales->transferPayments($cakePointSale, $sale);
                }

                if ($cakePointSale->ready_at && ! $sale->ready_at) {
                    $sale->update(['ready_at' => $cakePointSale->ready_at]);
                }

                $this->cakePoint->completeFromSale($cakePointSale, $sale);
            }
        }

        $paymentWarning = null;
        if ($advanceAmount > 0) {
            $sale->refresh();
            $leavesBalance = $advanceAmount < (float) $sale->total;
            $balanceDueDate = $leavesBalance
                ? ($validated['credit_repayment_date']
                    ?? ($sale->ready_at?->toDateString() ?: now()->toDateString()))
                : null;

            try {
                $this->sales->pay(
                    $request->user(),
                    $sale,
                    $advanceAmount,
                    $validated['payment_method'],
                    isset($validated['payment_provider_id']) ? (int) $validated['payment_provider_id'] : null,
                    $validated['payment_reference'] ?? null,
                    $customerId,
                    $validated['customer_name'] ?? null,
                    $validated['customer_phone'] ?? null,
                    $balanceDueDate,
                );
                $sale->refresh();
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $paymentWarning = collect($exception->errors())->flatten()->first()
                    ?: 'Sale saved, but payment could not be recorded.';
            }
        }

        $shouldSendCustomerSms = $request->boolean('send_customer_sms')
            && $this->cartRequiresOrderFlow($validated['items'] ?? []);
        $smsWarning = null;
        if ($shouldSendCustomerSms) {
            $sale->loadMissing(['items.item']);
            $smsWarning = $this->sendCustomerSaleConfirmation($request->user(), $sale, $customerId);
        }

        $message = "Sale {$sale->sale_number} recorded.";
        if ($sale->ready_at) {
            $message .= ' Ready '.$sale->ready_at->format('d M Y H:i').'.';
        }
        if ($advanceAmount > 0 && ! $paymentWarning) {
            $message .= ' Payment '.number_format($advanceAmount, 0).' TZS recorded.';
        }
        if ($sale->hasOutstandingBalance()) {
            $message .= ' Balance due: '.$sale->formattedBalanceDue().'.';
        }
        if ($shouldSendCustomerSms && ! $smsWarning) {
            $message .= ' Confirmation SMS sent to customer.';
        }

        $warnings = collect([$paymentWarning, $smsWarning])->filter()->implode(' ');

        if ($warnings !== '') {
            return redirect()
                ->route('sales.index')
                ->with('warning', trim($message.' '.$warnings));
        }

        return redirect()
            ->route('sales.index')
            ->with('success', $message);
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

        $sale->load(['items', 'ingredientUsages', 'customer']);

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
                'item_name' => $line->item_name,
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
        $advanceAmount = (float) ($validated['advance_amount'] ?? 0);

        if ($advanceAmount > 0 && empty($validated['payment_method'])) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Choose a payment method for the amount paid now.');
        }

        $customerId = isset($validated['customer_id']) ? (int) $validated['customer_id'] : null;
        $customerId = $this->syncCustomerBirthday(
            $customerId,
            $validated['customer_name'] ?? null,
            $validated['customer_phone'] ?? null,
            $validated['customer_birthday'] ?? null,
        );

        // Sale forms no longer collect ingredients; keep any existing usage rows on edit.
        $ingredients = $validated['ingredients'];
        if ($ingredients === []) {
            $sale->loadMissing('ingredientUsages');
            $ingredients = $sale->ingredientUsages->map(fn ($usage) => [
                'ingredient_id' => (int) $usage->ingredient_id,
                'quantity_used' => (float) $usage->quantity_used,
            ])->values()->all();
        }

        $sale = $this->sales->update(
            $sale,
            collect($validated['items'])->values()->all(),
            $ingredients,
            $customerId,
            $validated['customer_name'] ?? null,
            $validated['customer_phone'] ?? null,
            $validated['notes'] ?? null,
            $validated['sold_at'],
            $validated['ready_at'] ?? null,
        );

        $paymentWarning = null;
        if ($advanceAmount > 0) {
            $sale->refresh();
            $leavesBalance = $advanceAmount < (float) $sale->total;
            $balanceDueDate = $leavesBalance
                ? ($validated['credit_repayment_date']
                    ?? ($sale->ready_at?->toDateString() ?: now()->toDateString()))
                : null;

            try {
                $this->sales->pay(
                    $request->user(),
                    $sale,
                    $advanceAmount,
                    $validated['payment_method'],
                    isset($validated['payment_provider_id']) ? (int) $validated['payment_provider_id'] : null,
                    $validated['payment_reference'] ?? null,
                    $customerId,
                    $validated['customer_name'] ?? null,
                    $validated['customer_phone'] ?? null,
                    $balanceDueDate,
                );
                $sale->refresh();
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $paymentWarning = collect($exception->errors())->flatten()->first()
                    ?: 'Sale updated, but payment could not be recorded.';
            }
        }

        $message = "Sale {$sale->sale_number} updated successfully.";
        if ($advanceAmount > 0 && ! $paymentWarning) {
            $message .= ' Payment '.number_format($advanceAmount, 0).' TZS recorded.';
        }

        if ($paymentWarning) {
            return redirect()
                ->route('sales.show', $sale)
                ->with('warning', $message.' '.$paymentWarning);
        }

        return redirect()
            ->route('sales.index')
            ->with('success', $message);
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

        $paymentAmount = round((float) $validated['amount'], 2);
        $leavesBalance = $validated['payment_method'] === Sale::PAYMENT_CREDIT
            || $paymentAmount < round((float) $sale->balanceDue(), 2);

        if ($leavesBalance) {
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
        $requiresOrderFlow = $this->cartRequiresOrderFlow($request->input('items', []));

        $validated = $request->validate([
            'cake_point_sale_id' => ['nullable', 'exists:sales,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_birthday' => ['nullable', 'date'],
            'sold_at' => ['required', 'date'],
            'ready_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'advance_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'payment_method' => ['nullable', Rule::in(array_keys(Sale::paymentMethodOptions()))],
            'payment_provider_id' => ['nullable', 'exists:payment_providers,id'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'credit_repayment_date' => ['nullable', 'date'],
            'send_customer_sms' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'exists:items,id'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.9999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.ingredient_id' => ['nullable', 'exists:ingredients,id'],
            'ingredients.*.quantity_used' => ['nullable', 'numeric', 'gt:0', 'max:999999999.9999'],
        ]);

        $validated['ingredients'] = $this->normalizedIngredientUsages($validated['ingredients'] ?? []);

        if (! empty($validated['customer_id'])) {
            $customer = Customer::query()->find($validated['customer_id']);
            if ($customer) {
                $validated['customer_name'] = $validated['customer_name'] ?: $customer->name;
                $validated['customer_phone'] = $validated['customer_phone'] ?: $customer->phone;
            }
        }

        if (! $requiresOrderFlow) {
            $validated['ready_at'] = null;
            $validated['customer_birthday'] = null;
            $validated['send_customer_sms'] = false;
        }

        $cartTotal = $this->cartTotalFromItems($validated['items'] ?? []);
        $amountPaid = (float) ($validated['advance_amount'] ?? 0);
        $leavesBalance = $cartTotal > 0 && $amountPaid > 0 && $amountPaid < $cartTotal;
        $requiresCustomer = $requiresOrderFlow || ($cartTotal > 0 && $amountPaid < $cartTotal);

        if ($requiresCustomer) {
            if (! filled($validated['customer_name'] ?? null)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'customer_name' => $requiresOrderFlow
                        ? 'Customer name is required for order items.'
                        : 'Customer name is required when amount paid is less than the total.',
                ]);
            }

            if (! filled($validated['customer_phone'] ?? null)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'customer_phone' => $requiresOrderFlow
                        ? 'Customer phone is required for order items.'
                        : 'Customer phone is required when amount paid is less than the total.',
                ]);
            }
        }

        if ($leavesBalance && ! filled($validated['credit_repayment_date'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'credit_repayment_date' => 'Please set the balance due date for the remaining amount.',
            ]);
        }

        if (! $leavesBalance) {
            $validated['credit_repayment_date'] = null;
        }

        return $validated;
    }

    /**
     * @param  array<int, array{quantity?: mixed, unit_price?: mixed, discount?: mixed}>  $items
     */
    private function cartTotalFromItems(array $items): float
    {
        return (float) collect($items)->sum(function (array $row) {
            $qty = (float) ($row['quantity'] ?? 0);
            $price = (float) ($row['unit_price'] ?? 0);
            $discount = (float) ($row['discount'] ?? 0);
            $line = ($qty * $price) - $discount;

            return $line > 0 ? $line : 0;
        });
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private function cartRequiresOrderFlow(array $items): bool
    {
        $itemIds = collect($items)
            ->pluck('item_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($itemIds === []) {
            return false;
        }

        return Item::query()
            ->whereIn('id', $itemIds)
            ->where('sale_type', Item::SALE_TYPE_ORDER)
            ->exists();
    }

    private function salesRequireIngredients(): bool
    {
        return $this->settings->get('sales_require_ingredients', '1') === '1';
    }

    /**
     * @param  array<int, array{ingredient_id?: mixed, quantity_used?: mixed}>  $usages
     * @return list<array{ingredient_id: int, quantity_used: float}>
     */
    private function normalizedIngredientUsages(array $usages): array
    {
        return collect($usages)
            ->filter(function (array $usage) {
                return filled($usage['ingredient_id'] ?? null)
                    && (float) ($usage['quantity_used'] ?? 0) > 0;
            })
            ->map(fn (array $usage) => [
                'ingredient_id' => (int) $usage['ingredient_id'],
                'quantity_used' => (float) $usage['quantity_used'],
            ])
            ->values()
            ->all();
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
            'requireIngredients' => $this->salesRequireIngredients(),
            'itemOptions' => $items->map(fn (Item $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'price' => (float) $item->price,
                'unit' => $item->packageUnit?->symbol,
                'sale_type' => $item->sale_type ?: Item::SALE_TYPE_RETAIL,
                'is_custom' => (bool) $item->is_custom,
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
                'birthday' => $customer->birthday?->format('Y-m-d'),
                'label' => $customer->displayLabel(),
            ])->values(),
            'paymentProviderOptions' => $this->paymentProviderOptions(),
        ];
    }

    private function sendCustomerSaleConfirmation(User $user, Sale $sale, ?int $customerId): ?string
    {
        if (! filled($sale->customer_phone)) {
            return 'Sale saved, but confirmation SMS was skipped (no customer phone).';
        }

        if (! $this->sms->isReady()) {
            return 'Sale saved, but confirmation SMS was not sent (SMS is not enabled or configured).';
        }

        $phone = $this->sms->normalizePhone($sale->customer_phone);

        if (! $phone) {
            return 'Sale saved, but confirmation SMS was skipped (invalid customer phone).';
        }

        $body = $this->sms->buildSaleConfirmationMessage($sale);

        $smsMessage = SmsMessage::create([
            'created_by_user_id' => $user->id,
            'recipient_phone' => $phone,
            'recipient_name' => $sale->customer_name,
            'recipient_type' => SmsMessage::RECIPIENT_CUSTOMER,
            'recipient_id' => $customerId,
            'type' => SmsMessage::TYPE_SALE_CONFIRMATION,
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

            return null;
        } catch (\Throwable $exception) {
            $smsMessage->update([
                'status' => SmsMessage::STATUS_FAILED,
                'last_error' => $exception->getMessage(),
            ]);

            Log::warning('Sale confirmation SMS failed', [
                'sale_id' => $sale->id,
                'error' => $exception->getMessage(),
            ]);

            return 'Sale saved, but confirmation SMS failed: '.$exception->getMessage();
        }
    }

    private function syncCustomerBirthday(
        ?int $customerId,
        ?string $customerName,
        ?string $customerPhone,
        ?string $birthday,
    ): ?int {
        if (! filled($birthday)) {
            return $customerId;
        }

        if ($customerId) {
            Customer::query()->whereKey($customerId)->update(['birthday' => $birthday]);

            return $customerId;
        }

        if (! filled($customerPhone)) {
            return null;
        }

        $customer = Customer::query()->where('phone', $customerPhone)->first();

        if ($customer) {
            $customer->update([
                'birthday' => $birthday,
                'name' => filled($customerName) ? $customerName : $customer->name,
            ]);

            return $customer->id;
        }

        if (! filled($customerName)) {
            return null;
        }

        $customer = Customer::create([
            'name' => $customerName,
            'phone' => $customerPhone,
            'birthday' => $birthday,
            'is_active' => true,
        ]);

        return $customer->id;
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
