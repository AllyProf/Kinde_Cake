<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\IngredientReceiving;
use App\Services\IngredientStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IngredientReceivingController extends Controller
{
    public function __construct(private IngredientStockService $stock) {}

    public function index(): View
    {
        $receivings = IngredientReceiving::query()
            ->with(['ingredient.receivingPackageUnit', 'ingredient.usagePackageUnit', 'user'])
            ->latest('received_at')
            ->latest('id')
            ->paginate(10);

        return view('receivings.index', compact('receivings'));
    }

    public function create(): View
    {
        return view('receivings.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ingredient_id' => ['required', 'exists:ingredients,id'],
            'receive_mode' => ['required', 'in:package,usage'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.9999'],
            'received_at' => ['required', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $ingredient = Ingredient::query()
            ->where('is_active', true)
            ->findOrFail($validated['ingredient_id']);

        $this->stock->receive(
            $ingredient,
            $request->user(),
            (float) $validated['quantity'],
            $validated['receive_mode'],
            $validated['received_at'],
            $validated['supplier'] ?? null,
            $validated['notes'] ?? null,
            (float) $validated['purchase_cost'],
        );

        return redirect()
            ->route('receivings.index')
            ->with('success', 'Stock received successfully.');
    }

    public function destroy(IngredientReceiving $receiving): RedirectResponse
    {
        try {
            $this->stock->cancel($receiving);
        } catch (ValidationException $exception) {
            return redirect()
                ->back()
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()
            ->route('receivings.index')
            ->with('success', 'Receiving cancelled and stock reversed.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        $ingredients = Ingredient::query()
            ->with(['receivingPackageUnit', 'usagePackageUnit'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return [
            'ingredients' => $ingredients,
            'ingredientOptions' => $ingredients->map(fn (Ingredient $ingredient) => [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'receiving_name' => $ingredient->receivingPackageUnit?->name,
                'receiving_symbol' => $ingredient->receivingPackageUnit?->symbol,
                'usage_name' => $ingredient->usagePackageUnit?->name,
                'usage_symbol' => $ingredient->usagePackageUnit?->symbol,
                'usage_per_receiving' => (float) $ingredient->usage_per_receiving,
                'stock_quantity' => (float) $ingredient->stock_quantity,
            ])->values(),
        ];
    }
}
