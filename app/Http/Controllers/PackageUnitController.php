<?php

namespace App\Http\Controllers;

use App\Models\PackageUnit;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackageUnitController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'symbol' => ['required', 'string', 'max:20', 'unique:package_units,symbol'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        PackageUnit::create([
            'name' => $validated['name'],
            'symbol' => strtolower($validated['symbol']),
            'description' => $validated['description'] ?? null,
            'is_builtin' => false,
            'is_active' => true,
        ]);

        return redirect()
            ->route('settings.index', ['tab' => 'packages'])
            ->with('success', 'Package unit added successfully.');
    }

    public function update(Request $request, PackageUnit $packageUnit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'symbol' => ['required', 'string', 'max:20', Rule::unique('package_units', 'symbol')->ignore($packageUnit->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $packageUnit->update([
            'name' => $validated['name'],
            'symbol' => strtolower($validated['symbol']),
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('settings.index', ['tab' => 'packages'])
            ->with('success', 'Package unit updated successfully.');
    }

    public function destroy(PackageUnit $packageUnit): RedirectResponse
    {
        $packageUnit->delete();

        return redirect()
            ->route('settings.index', ['tab' => 'packages'])
            ->with('success', 'Package unit removed successfully.');
    }

    public function importDefaults(): RedirectResponse
    {
        $count = $this->catalog->importPackages();

        return redirect()
            ->route('settings.index', ['tab' => 'packages'])
            ->with('success', $count > 0
                ? "{$count} built-in package unit".($count === 1 ? '' : 's').' imported.'
                : 'All built-in package units are already imported.');
    }
}
