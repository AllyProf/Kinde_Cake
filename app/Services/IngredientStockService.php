<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientReceiving;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IngredientStockService
{
    public const MODE_PACKAGE = 'package';

    public const MODE_USAGE = 'usage';

    public function receive(
        Ingredient $ingredient,
        User $user,
        float $entryQuantity,
        string $receiveMode,
        string $receivedAt,
        ?string $supplier = null,
        ?string $notes = null,
        float $purchaseCost = 0,
    ): IngredientReceiving {
        return DB::transaction(function () use ($ingredient, $user, $entryQuantity, $receiveMode, $receivedAt, $supplier, $notes, $purchaseCost) {
            $usagePerReceiving = (float) $ingredient->usage_per_receiving;

            if ($receiveMode === self::MODE_USAGE) {
                $usageAdded = round($entryQuantity, 4);
                $quantityReceived = round($entryQuantity / $usagePerReceiving, 4);
            } else {
                $quantityReceived = round($entryQuantity, 4);
                $usageAdded = round($entryQuantity * $usagePerReceiving, 4);
            }

            $receiving = IngredientReceiving::create([
                'ingredient_id' => $ingredient->id,
                'receive_mode' => $receiveMode,
                'user_id' => $user->id,
                'quantity_received' => $quantityReceived,
                'entry_quantity' => $entryQuantity,
                'usage_quantity_added' => $usageAdded,
                'usage_per_receiving' => $usagePerReceiving,
                'purchase_cost' => round($purchaseCost, 2),
                'received_at' => $receivedAt,
                'supplier' => $supplier,
                'notes' => $notes,
                'status' => IngredientReceiving::STATUS_ACTIVE,
            ]);

            $ingredient->increment('stock_quantity', $usageAdded);

            return $receiving;
        });
    }

    public function cancel(IngredientReceiving $receiving): void
    {
        DB::transaction(function () use ($receiving) {
            $receiving = IngredientReceiving::query()->lockForUpdate()->findOrFail($receiving->id);

            if ($receiving->isCancelled()) {
                throw ValidationException::withMessages([
                    'receiving' => 'This receiving has already been cancelled.',
                ]);
            }

            $ingredient = Ingredient::query()->lockForUpdate()->findOrFail($receiving->ingredient_id);
            $usageToReverse = round((float) $receiving->usage_quantity_added, 4);

            if ((float) $ingredient->stock_quantity < $usageToReverse) {
                throw ValidationException::withMessages([
                    'receiving' => 'Cannot cancel because part of this stock has already been used.',
                ]);
            }

            $ingredient->decrement('stock_quantity', $usageToReverse);

            $receiving->update([
                'status' => IngredientReceiving::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);
        });
    }

    public function deduct(Ingredient $ingredient, float $usageQuantity): void
    {
        if ($usageQuantity <= 0) {
            return;
        }

        $ingredient->decrement('stock_quantity', round($usageQuantity, 4));
    }

    public function restore(Ingredient $ingredient, float $usageQuantity): void
    {
        if ($usageQuantity <= 0) {
            return;
        }

        $ingredient->increment('stock_quantity', round($usageQuantity, 4));
    }
}
