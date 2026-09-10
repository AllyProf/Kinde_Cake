<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(): View
    {
        $ingredients = Ingredient::query()
            ->with(['receivingPackageUnit', 'usagePackageUnit'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $lowStockItems = $ingredients->filter(
            fn (Ingredient $ingredient) => $ingredient->isLowStock() && (float) $ingredient->stock_quantity > 0
        )->values();

        $outOfStockItems = $ingredients->filter(
            fn (Ingredient $ingredient) => (float) $ingredient->stock_quantity <= 0
        )->values();

        return view('stock.index', [
            'ingredients' => $ingredients,
            'lowStockItems' => $lowStockItems,
            'outOfStockItems' => $outOfStockItems,
        ]);
    }
}
