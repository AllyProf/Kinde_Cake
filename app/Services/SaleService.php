<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Item;
use App\Models\PaymentProvider;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private IngredientStockService $stock) {}

    /**
     * Create a pending sale for the cake point (no ingredient stock deduction).
     *
     * @param  array<int, array{item_id: int, quantity: float, unit_price?: float, discount?: float}>  $items
     */
    public function createCakePointOrder(
        User $user,
        array $items,
        ?int $customerId,
        ?string $customerName,
        ?string $customerPhone,
        ?string $notes,
    ): Sale {
        return DB::transaction(function () use ($user, $items, $customerId, $customerName, $customerPhone, $notes) {
            $saleItems = $this->buildSaleItems(
                collect($items)->map(function (array $row) {
                    if (! isset($row['unit_price'])) {
                        $item = Item::query()->where('is_active', true)->find($row['item_id']);
                        $row['unit_price'] = $item ? (float) $item->price : 0;
                    }

                    return $row;
                })->values()->all(),
            );

            $total = array_sum(array_column($saleItems, 'line_total'));

            $sale = Sale::create([
                'sale_number' => $this->generateSaleNumber(),
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'total' => $total,
                'status' => Sale::STATUS_PENDING,
                'notes' => $notes,
                'sold_at' => now(),
            ]);

            $this->persistLines($sale, $saleItems, []);

            return $sale->load(['items.item', 'user']);
        });
    }

    /**
     * @param  array<int, array{item_id: int, quantity: float, unit_price: float, discount?: float}>  $items
     * @param  array<int, array{ingredient_id: int, quantity_used: float}>  $usages
     */
    public function create(
        User $user,
        array $items,
        array $usages,
        ?int $customerId,
        ?string $customerName,
        ?string $customerPhone,
        ?string $notes,
        string $soldAt,
    ): Sale {
        return DB::transaction(function () use ($user, $items, $usages, $customerId, $customerName, $customerPhone, $notes, $soldAt) {
            $this->validateStock($usages);

            $saleItems = $this->buildSaleItems($items);
            $total = array_sum(array_column($saleItems, 'line_total'));

            $sale = Sale::create([
                'sale_number' => $this->generateSaleNumber(),
                'user_id' => $user->id,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'total' => $total,
                'status' => Sale::STATUS_PENDING,
                'notes' => $notes,
                'sold_at' => $soldAt,
            ]);

            $this->persistLines($sale, $saleItems, $usages);

            return $sale->load(['items.item', 'ingredientUsages.ingredient.usagePackageUnit', 'user']);
        });
    }

    /**
     * @param  array<int, array{item_id: int, quantity: float, unit_price: float, discount?: float}>  $items
     * @param  array<int, array{ingredient_id: int, quantity_used: float}>  $usages
     */
    public function update(
        Sale $sale,
        array $items,
        array $usages,
        ?int $customerId,
        ?string $customerName,
        ?string $customerPhone,
        ?string $notes,
        string $soldAt,
    ): Sale {
        return DB::transaction(function () use ($sale, $items, $usages, $customerId, $customerName, $customerPhone, $notes, $soldAt) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if (! $sale->canBeEdited()) {
                throw ValidationException::withMessages([
                    'sale' => 'Only pending sales without payments can be edited.',
                ]);
            }

            $sale->load(['ingredientUsages']);

            foreach ($sale->ingredientUsages as $usage) {
                $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($usage->ingredient_id);
                $this->stock->restore($ingredient, (float) $usage->quantity_used);
            }

            $sale->items()->delete();
            $sale->ingredientUsages()->delete();

            $this->validateStock($usages);

            $saleItems = $this->buildSaleItems($items);
            $total = array_sum(array_column($saleItems, 'line_total'));

            $sale->update([
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'total' => $total,
                'notes' => $notes,
                'sold_at' => $soldAt,
                'edited_at' => now(),
            ]);

            $this->persistLines($sale, $saleItems, $usages);

            return $sale->load(['items.item', 'ingredientUsages.ingredient.usagePackageUnit', 'user']);
        });
    }

    public function pay(
        User $user,
        Sale $sale,
        float $amount,
        string $paymentMethod,
        ?int $paymentProviderId = null,
        ?string $paymentReference = null,
        ?int $customerId = null,
        ?string $customerName = null,
        ?string $customerPhone = null,
        ?string $creditRepaymentDate = null,
    ): Sale {
        return DB::transaction(function () use (
            $user,
            $sale,
            $amount,
            $paymentMethod,
            $paymentProviderId,
            $paymentReference,
            $customerId,
            $customerName,
            $customerPhone,
            $creditRepaymentDate,
        ) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if (! $sale->canBePaid()) {
                throw ValidationException::withMessages([
                    'payment' => 'This sale cannot receive payments.',
                ]);
            }

            $balanceDue = $sale->balanceDue();

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must be greater than zero.',
                ]);
            }

            if ($amount > $balanceDue) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot exceed the balance due ('.number_format($balanceDue, 0).' TZS).',
                ]);
            }

            $paidAt = now();
            $isCreditPayment = $paymentMethod === Sale::PAYMENT_CREDIT;

            if ($isCreditPayment) {
                $newAmountPaid = (float) $sale->amount_paid;
                $isFullyPaid = false;
                $requiresBalanceInfo = true;
            } else {
                $newAmountPaid = round((float) $sale->amount_paid + $amount, 2);
                $isFullyPaid = $newAmountPaid >= (float) $sale->total;
                $requiresBalanceInfo = ! $isFullyPaid;
            }

            $this->validatePayment(
                $paymentMethod,
                $paymentProviderId,
                $requiresBalanceInfo,
                $customerName,
                $customerId,
                $creditRepaymentDate,
            );

            $sale->payments()->create([
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'payment_provider_id' => in_array($paymentMethod, [Sale::PAYMENT_MOBILE, Sale::PAYMENT_BANK], true)
                    ? $paymentProviderId
                    : null,
                'payment_reference' => in_array($paymentMethod, [Sale::PAYMENT_MOBILE, Sale::PAYMENT_BANK], true)
                    ? $paymentReference
                    : null,
                'customer_id' => $requiresBalanceInfo ? $customerId : null,
                'customer_name' => $requiresBalanceInfo ? $customerName : null,
                'customer_phone' => $requiresBalanceInfo ? $customerPhone : null,
                'credit_repayment_date' => $requiresBalanceInfo ? $creditRepaymentDate : null,
                'paid_at' => $paidAt,
            ]);

            $update = [
                'amount_paid' => $newAmountPaid,
                'status' => $isFullyPaid
                    ? Sale::STATUS_PAID
                    : ($newAmountPaid > 0 ? Sale::STATUS_PARTIAL : Sale::STATUS_PENDING),
                'payment_method' => $paymentMethod,
                'payment_provider_id' => in_array($paymentMethod, [Sale::PAYMENT_MOBILE, Sale::PAYMENT_BANK], true)
                    ? $paymentProviderId
                    : null,
                'payment_reference' => in_array($paymentMethod, [Sale::PAYMENT_MOBILE, Sale::PAYMENT_BANK], true)
                    ? $paymentReference
                    : null,
                'credit_repayment_date' => $isFullyPaid ? null : $creditRepaymentDate,
                'paid_at' => $isFullyPaid ? $paidAt : null,
            ];

            if ($requiresBalanceInfo) {
                $update['customer_id'] = $customerId;
                $update['customer_name'] = $customerName;
                $update['customer_phone'] = $customerPhone;
                $update['credit_repayment_date'] = $creditRepaymentDate;
            }

            $sale->update($update);

            return $sale->load(['paymentProvider', 'payments.paymentProvider', 'payments.user']);
        });
    }

    public function destroy(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if (! $sale->isPending() || (float) $sale->amount_paid > 0) {
                throw ValidationException::withMessages([
                    'sale' => 'Only unpaid sales without payments can be deleted.',
                ]);
            }

            $sale->load(['ingredientUsages']);

            foreach ($sale->ingredientUsages as $usage) {
                $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($usage->ingredient_id);
                $this->stock->restore($ingredient, (float) $usage->quantity_used);
            }

            $sale->update([
                'status' => Sale::STATUS_DELETED,
                'deleted_at' => now(),
            ]);
        });
    }

    /** @param array<int, array{ingredient_id: int, quantity_used: float}> $usages */
    private function validateStock(array $usages): void
    {
        $required = [];

        foreach ($usages as $usage) {
            $qty = (float) $usage['quantity_used'];
            if ($qty <= 0) {
                continue;
            }

            $id = (int) $usage['ingredient_id'];
            $required[$id] = ($required[$id] ?? 0) + $qty;
        }

        foreach ($required as $ingredientId => $needed) {
            $ingredient = Ingredient::query()->find($ingredientId);

            if (! $ingredient || ! $ingredient->is_active) {
                throw ValidationException::withMessages([
                    'ingredients' => 'One or more selected ingredients are invalid.',
                ]);
            }

            if ((float) $ingredient->stock_quantity < $needed) {
                throw ValidationException::withMessages([
                    'ingredients' => "Not enough stock for {$ingredient->name}. Available: {$ingredient->formattedStock()}.",
                ]);
            }
        }
    }

    private function validatePayment(
        string $paymentMethod,
        ?int $paymentProviderId,
        bool $requiresBalanceInfo,
        ?string $customerName = null,
        ?int $customerId = null,
        ?string $creditRepaymentDate = null,
    ): void {
        $allowed = array_keys(Sale::paymentMethodOptions());

        if (! in_array($paymentMethod, $allowed, true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Invalid payment method selected.',
            ]);
        }

        if (in_array($paymentMethod, [Sale::PAYMENT_MOBILE, Sale::PAYMENT_BANK], true)) {
            if (! $paymentProviderId) {
                throw ValidationException::withMessages([
                    'payment_provider_id' => 'Please select a payment provider.',
                ]);
            }

            $provider = PaymentProvider::query()
                ->where('is_active', true)
                ->find($paymentProviderId);

            if (! $provider) {
                throw ValidationException::withMessages([
                    'payment_provider_id' => 'Selected payment provider is invalid.',
                ]);
            }

            $expectedType = $paymentMethod === Sale::PAYMENT_MOBILE
                ? PaymentProvider::TYPE_MOBILE
                : PaymentProvider::TYPE_BANK;

            if ($provider->type !== $expectedType) {
                throw ValidationException::withMessages([
                    'payment_provider_id' => 'Selected provider does not match the payment method.',
                ]);
            }
        }

        if ($requiresBalanceInfo) {
            if (! $creditRepaymentDate) {
                throw ValidationException::withMessages([
                    'credit_repayment_date' => 'Please set the expected repayment date for the remaining balance.',
                ]);
            }

            if (! $customerId && ! filled($customerName)) {
                throw ValidationException::withMessages([
                    'customer_name' => 'Please record customer details for the remaining balance.',
                ]);
            }
        }
    }

    /**
     * @param  list<array{item_id: int, quantity: float, unit_price: float, discount: float, line_total: float}>  $saleItems
     * @param  array<int, array{ingredient_id: int, quantity_used: float}>  $usages
     */
    private function persistLines(Sale $sale, array $saleItems, array $usages): void
    {
        foreach ($saleItems as $row) {
            $sale->items()->create($row);
        }

        foreach ($usages as $usage) {
            if ((float) $usage['quantity_used'] <= 0) {
                continue;
            }

            $sale->ingredientUsages()->create([
                'ingredient_id' => $usage['ingredient_id'],
                'quantity_used' => $usage['quantity_used'],
            ]);

            $this->stock->deduct(
                Ingredient::query()->lockForUpdate()->findOrFail($usage['ingredient_id']),
                (float) $usage['quantity_used'],
            );
        }
    }

    /** @param array<int, array{item_id: int, quantity: float, unit_price: float, discount?: float}> $items
     * @return list<array{item_id: int, quantity: float, unit_price: float, discount: float, line_total: float}>
     */
    private function buildSaleItems(array $items): array
    {
        $rows = [];

        foreach ($items as $row) {
            $item = Item::query()->where('is_active', true)->find($row['item_id']);

            if (! $item) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected items are invalid.',
                ]);
            }

            $quantity = (float) $row['quantity'];
            $unitPrice = (float) $row['unit_price'];
            $discount = max(0, (float) ($row['discount'] ?? 0));
            $lineSubtotal = round($quantity * $unitPrice, 2);

            if ($discount > $lineSubtotal) {
                throw ValidationException::withMessages([
                    'items' => "Discount for {$item->name} cannot exceed the line total.",
                ]);
            }

            $lineTotal = round($lineSubtotal - $discount, 2);

            $rows[] = [
                'item_id' => $item->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'line_total' => $lineTotal,
            ];
        }

        return $rows;
    }

    private function generateSaleNumber(): string
    {
        $prefix = 'SALE-'.now()->format('Ymd');
        $latest = Sale::query()
            ->where('sale_number', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('sale_number');

        $sequence = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('%s-%04d', $prefix, $sequence);
    }
}
