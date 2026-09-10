<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\PackageUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IngredientController extends Controller
{
    public function index(): View
    {
        $ingredients = Ingredient::query()
            ->with(['receivingPackageUnit', 'usagePackageUnit'])
            ->orderBy('name')
            ->paginate(10);

        return view('ingredients.index', compact('ingredients'));
    }

    public function create(): View
    {
        return view('ingredients.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateIngredient($request);

        Ingredient::create([
            'name' => $validated['name'],
            'receiving_package_unit_id' => $validated['receiving_package_unit_id'],
            'usage_package_unit_id' => $validated['usage_package_unit_id'],
            'usage_per_receiving' => $validated['usage_per_receiving'],
            'reorder_level' => $validated['reorder_level'] ?? 0,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Ingredient registered successfully.');
    }

    public function edit(Ingredient $ingredient): View
    {
        return view('ingredients.edit', array_merge($this->formData(), compact('ingredient')));
    }

    public function update(Request $request, Ingredient $ingredient): RedirectResponse
    {
        $validated = $this->validateIngredient($request, $ingredient);

        $ingredient->update([
            'name' => $validated['name'],
            'receiving_package_unit_id' => $validated['receiving_package_unit_id'],
            'usage_package_unit_id' => $validated['usage_package_unit_id'],
            'usage_per_receiving' => $validated['usage_per_receiving'],
            'reorder_level' => $validated['reorder_level'] ?? 0,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Ingredient updated successfully.');
    }

    public function destroy(Ingredient $ingredient): RedirectResponse
    {
        if ($ingredient->receivings()->exists()) {
            return redirect()
                ->route('ingredients.index')
                ->with('error', 'Cannot delete an ingredient that has receiving records.');
        }

        $ingredient->delete();

        return redirect()
            ->route('ingredients.index')
            ->with('success', 'Ingredient removed successfully.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'packageUnits' => PackageUnit::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function validateIngredient(Request $request, ?Ingredient $ingredient = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ingredients', 'name')->ignore($ingredient?->id),
            ],
            'receiving_package_unit_id' => [
                'required',
                Rule::exists('package_units', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'usage_package_unit_id' => [
                'required',
                Rule::exists('package_units', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'usage_per_receiving' => ['required', 'numeric', 'gt:0', 'max:999999999.9999'],
            'reorder_level' => ['nullable', 'numeric', 'min:0', 'max:999999999.9999'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
