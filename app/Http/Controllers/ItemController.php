<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\PackageUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(): View
    {
        $items = Item::query()
            ->with(['category', 'packageUnit'])
            ->latest()
            ->get();

        return view('items.index', [
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('items.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateItem($request);

        $isCustom = $request->boolean('is_custom');

        Item::create([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'package_unit_id' => $validated['package_unit_id'],
            'price' => $isCustom ? 0 : $validated['price'],
            'sale_type' => $isCustom ? Item::SALE_TYPE_ORDER : $validated['sale_type'],
            'is_custom' => $isCustom,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('items.index')
            ->with('success', 'Item registered successfully.');
    }

    public function edit(Item $item): View
    {
        return view('items.edit', array_merge($this->formData(), compact('item')));
    }

    public function update(Request $request, Item $item): RedirectResponse
    {
        $validated = $this->validateItem($request, $item);

        $isCustom = $request->boolean('is_custom');

        $item->update([
            'name' => $validated['name'],
            'category_id' => $validated['category_id'],
            'package_unit_id' => $validated['package_unit_id'],
            'price' => $isCustom ? 0 : $validated['price'],
            'sale_type' => $isCustom ? Item::SALE_TYPE_ORDER : $validated['sale_type'],
            'is_custom' => $isCustom,
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('items.index')
            ->with('success', 'Item updated successfully.');
    }

    public function destroy(Item $item): RedirectResponse
    {
        $item->delete();

        return redirect()
            ->route('items.index')
            ->with('success', 'Item removed successfully.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'packageUnits' => PackageUnit::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function validateItem(Request $request, ?Item $item = null): array
    {
        $isCustom = $request->boolean('is_custom');

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('items', 'name')->ignore($item?->id),
            ],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'package_unit_id' => [
                'required',
                Rule::exists('package_units', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'price' => [$isCustom ? 'nullable' : 'required', 'numeric', 'min:0', 'max:999999999.99'],
            'sale_type' => [$isCustom ? 'nullable' : 'required', Rule::in(array_keys(Item::saleTypeOptions()))],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
