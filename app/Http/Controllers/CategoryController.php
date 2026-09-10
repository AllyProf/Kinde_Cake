<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_builtin' => false,
            'is_active' => true,
        ]);

        return redirect()
            ->route('settings.index', ['tab' => 'categories'])
            ->with('success', 'Category added successfully.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('settings.index', ['tab' => 'categories'])
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()
            ->route('settings.index', ['tab' => 'categories'])
            ->with('success', 'Category removed successfully.');
    }

    public function importDefaults(): RedirectResponse
    {
        $count = $this->catalog->importCategories();

        return redirect()
            ->route('settings.index', ['tab' => 'categories'])
            ->with('success', $count > 0
                ? "{$count} built-in categor".($count === 1 ? 'y' : 'ies').' imported.'
                : 'All built-in categories are already imported.');
    }
}
