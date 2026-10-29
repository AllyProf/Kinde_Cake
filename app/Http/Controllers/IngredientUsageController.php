<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\IngredientUsage;
use App\Services\IngredientStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IngredientUsageController extends Controller
{
    public function __construct(private IngredientStockService $stock) {}

    public function index(Request $request): View
    {
        $usages = IngredientUsage::query()
            ->with(['ingredient.usagePackageUnit', 'user'])
            ->latest('used_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $ingredients = Ingredient::query()
            ->with('usagePackageUnit')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('ingredient-usages.index', compact('usages', 'ingredients'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'exists:ingredients,id'],
            'quantity_used' => ['required', 'numeric', 'gt:0', 'max:999999999.9999'],
            'used_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $ingredient = Ingredient::query()->findOrFail($validated['ingredient_id']);

        try {
            $this->stock->recordUsage(
                $ingredient,
                $request->user(),
                (float) $validated['quantity_used'],
                $validated['used_at'],
                $validated['notes'] ?? null,
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()
                ->route('ingredient-usages.index')
                ->withInput()
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()
            ->route('ingredient-usages.index')
            ->with('success', "Recorded usage for {$ingredient->name}.");
    }
}
