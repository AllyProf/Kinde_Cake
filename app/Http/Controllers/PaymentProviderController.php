<?php

namespace App\Http\Controllers;

use App\Models\PaymentProvider;
use App\Services\CatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentProviderController extends Controller
{
    public function __construct(private CatalogService $catalog) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in([PaymentProvider::TYPE_MOBILE, PaymentProvider::TYPE_BANK])],
        ]);

        PaymentProvider::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'is_builtin' => false,
            'is_active' => true,
        ]);

        return redirect()
            ->route('settings.index', ['tab' => 'payments'])
            ->with('success', 'Payment provider added successfully.');
    }

    public function update(Request $request, PaymentProvider $paymentProvider): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in([PaymentProvider::TYPE_MOBILE, PaymentProvider::TYPE_BANK])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $paymentProvider->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('settings.index', ['tab' => 'payments'])
            ->with('success', 'Payment provider updated successfully.');
    }

    public function destroy(PaymentProvider $paymentProvider): RedirectResponse
    {
        $paymentProvider->delete();

        return redirect()
            ->route('settings.index', ['tab' => 'payments'])
            ->with('success', 'Payment provider removed successfully.');
    }

    public function importDefaults(): RedirectResponse
    {
        $count = $this->catalog->importPaymentProviders();

        return redirect()
            ->route('settings.index', ['tab' => 'payments'])
            ->with('success', $count > 0
                ? "{$count} built-in payment provider".($count === 1 ? '' : 's').' imported.'
                : 'All built-in payment providers are already imported.');
    }
}
