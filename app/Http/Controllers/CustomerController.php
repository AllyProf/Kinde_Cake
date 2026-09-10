<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\TanzaniaLocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private TanzaniaLocationService $locations) {}

    public function index(): View
    {
        $customers = Customer::query()
            ->latest()
            ->paginate(10);

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCustomer($request);

        Customer::create($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer registered successfully.');
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', array_merge($this->formData(), compact('customer')));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validateCustomer($request, $customer));

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->sales()->exists()) {
            return redirect()
                ->route('customers.index')
                ->with('error', 'Cannot delete a customer linked to sales.');
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer removed successfully.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'regions' => $this->locations->regions(),
            'customerLocations' => $this->locations->regionsWithDistricts(),
        ];
    }

    /** @return array<string, mixed> */
    private function validateCustomer(Request $request, ?Customer $customer = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'region' => ['nullable', 'string', Rule::in($this->locations->regions())],
            'district' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! empty($validated['district']) && ! $this->locations->isValidDistrict($validated['region'] ?? null, $validated['district'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'district' => 'Selected district is invalid for the chosen region.',
            ]);
        }

        if (! empty($validated['district']) && empty($validated['region'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'region' => 'Please select a region before choosing a district.',
            ]);
        }

        if (empty($validated['region'])) {
            $validated['district'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
