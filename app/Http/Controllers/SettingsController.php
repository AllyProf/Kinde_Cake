<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\PackageUnit;
use App\Models\PaymentProvider;
use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private AppSettingsService $settings) {}

    public function index(Request $request): View
    {
        $tab = $request->string('tab')->toString();
        if (! in_array($tab, ['business', 'appearance', 'categories', 'packages', 'payments', 'sms'], true)) {
            $tab = 'business';
        }

        return view('settings.index', [
            'tab' => $tab,
            'canManageSettings' => $request->user()->hasPermission('settings.manage'),
            'brandColor' => $this->settings->brandColor(),
            'panelBackgroundUrl' => $this->settings->panelBackgroundUrl(),
            'panelBackgroundDisplay' => $this->settings->panelBackgroundDisplay(),
            'business' => [
                'name' => $this->settings->get('business_name', ''),
                'tagline' => $this->settings->get('business_tagline', ''),
                'phone' => $this->settings->get('business_phone', ''),
                'email' => $this->settings->get('business_email', ''),
                'address' => $this->settings->get('business_address', ''),
                'city' => $this->settings->get('business_city', ''),
                'sales_require_ingredients' => $this->settings->get('sales_require_ingredients', '1') === '1',
            ],
            'sms' => [
                'enabled' => $this->settings->get('sms_enabled', '0') === '1',
                'driver' => $this->settings->get('sms_driver', config('sms.driver', 'mauzo_link')),
                'api_key' => $this->settings->get('sms_api_key', ''),
                'secret_key' => $this->settings->get('sms_secret_key', ''),
                'access_token' => $this->settings->get('sms_access_token', ''),
                'token_from_env' => filled(config('sms.mauzo_link.token'))
                    && ! filled($this->settings->get('sms_access_token')),
                'sender_id' => $this->settings->get('sms_sender_id', config('sms.mauzo_link.sender_id', '')),
                'order_received_template' => $this->settings->get(
                    'sms_cake_point_template',
                    config('sms.defaults.cake_point_template'),
                ),
                'sale_confirmation_template' => $this->settings->get(
                    'sms_sale_confirmation_template',
                    config('sms.defaults.sale_confirmation_template'),
                ),
                'cake_ready_template' => $this->settings->get(
                    'sms_cake_ready_template',
                    config('sms.defaults.cake_ready_template'),
                ),
                'birthday_enabled' => $this->settings->get('sms_birthday_enabled', '1') === '1',
                'birthday_reminder_days' => (int) ($this->settings->get(
                    'sms_birthday_reminder_days',
                    (string) config('sms.defaults.birthday_reminder_days', 7),
                ) ?? 7),
                'birthday_wish_template' => $this->settings->get(
                    'sms_birthday_wish_template',
                    config('sms.defaults.birthday_wish_template'),
                ),
                'birthday_reminder_template' => $this->settings->get(
                    'sms_birthday_reminder_template',
                    config('sms.defaults.birthday_reminder_template'),
                ),
                'day_close_owner_enabled' => $this->settings->get('sms_day_close_owner_enabled', '1') === '1',
                'day_close_owner_template' => $this->settings->get(
                    'sms_day_close_owner_template',
                    config('sms.defaults.day_close_owner_template'),
                ),
                'pickup_reminder_enabled' => $this->settings->get('sms_pickup_reminder_enabled', '1') === '1',
                'pickup_reminder_days' => (int) ($this->settings->get(
                    'sms_pickup_reminder_days',
                    (string) config('sms.defaults.pickup_reminder_days', 1),
                ) ?? 1),
                'pickup_reminder_template' => $this->settings->get(
                    'sms_pickup_reminder_template',
                    config('sms.defaults.pickup_reminder_template'),
                ),
                'low_stock_owner_enabled' => $this->settings->get('sms_low_stock_owner_enabled', '0') === '1',
                'low_stock_owner_template' => $this->settings->get(
                    'sms_low_stock_owner_template',
                    config('sms.defaults.low_stock_owner_template'),
                ),
            ],
            'categories' => Category::orderBy('name')->get(),
            'packages' => PackageUnit::orderBy('name')->get(),
            'paymentProviders' => PaymentProvider::orderBy('type')->orderBy('name')->get(),
            'builtinCategories' => config('catalog.categories', []),
            'builtinPackages' => config('catalog.packages', []),
            'builtinPaymentProviders' => config('catalog.payment_providers', []),
        ]);
    }

    public function updateBusiness(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'business_tagline' => ['nullable', 'string', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'business_email' => ['nullable', 'email', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:500'],
            'business_city' => ['nullable', 'string', 'max:120'],
            'sales_require_ingredients' => ['nullable', 'boolean'],
        ]);

        foreach ($validated as $key => $value) {
            if ($key === 'sales_require_ingredients') {
                continue;
            }

            $this->settings->set($key, $value);
        }

        $this->settings->set(
            'sales_require_ingredients',
            $request->boolean('sales_require_ingredients') ? '1' : '0'
        );

        return redirect()
            ->route('settings.index', ['tab' => 'business'])
            ->with('success', 'Business profile saved successfully.');
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'brand_color' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'panel_background' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'panel_background_display' => ['nullable', Rule::in(['cover', 'repeat'])],
            'remove_panel_background' => ['nullable', 'boolean'],
        ]);

        $normalized = $this->settings->normalizeHexColor($validated['brand_color']);

        if (! $normalized) {
            return back()
                ->withInput()
                ->with('error', 'Please choose a valid hex color.');
        }

        $this->settings->set('brand_color', $normalized);
        $this->settings->set(
            'panel_background_display',
            $validated['panel_background_display'] ?? $this->settings->panelBackgroundDisplay(),
        );

        if ($request->boolean('remove_panel_background')) {
            $this->settings->deletePanelBackground();
        }

        if ($request->hasFile('panel_background')) {
            $this->settings->deletePanelBackground();
            $path = $request->file('panel_background')->store('appearance', 'public');
            $this->settings->set('panel_background_path', $path);
        }

        return redirect()
            ->route('settings.index', ['tab' => 'appearance'])
            ->with('success', 'Appearance settings saved successfully.');
    }

    public function updateSms(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sms_enabled' => ['nullable', 'boolean'],
            'sms_driver' => ['required', Rule::in(['mauzo_link', 'beem', 'log'])],
            'sms_access_token' => ['nullable', 'string', 'max:255'],
            'sms_api_key' => ['nullable', 'string', 'max:255'],
            'sms_secret_key' => ['nullable', 'string', 'max:255'],
            'sms_sender_id' => ['nullable', 'string', 'max:20'],
            'sms_cake_point_template' => ['required', 'string', 'max:480'],
            'sms_sale_confirmation_template' => ['required', 'string', 'max:480'],
            'sms_cake_ready_template' => ['required', 'string', 'max:480'],
            'sms_birthday_enabled' => ['nullable', 'boolean'],
            'sms_birthday_reminder_days' => ['required', 'integer', 'min:1', 'max:30'],
            'sms_birthday_wish_template' => ['required', 'string', 'max:480'],
            'sms_birthday_reminder_template' => ['required', 'string', 'max:480'],
            'sms_day_close_owner_enabled' => ['nullable', 'boolean'],
            'sms_day_close_owner_template' => ['required', 'string', 'max:480'],
            'sms_pickup_reminder_enabled' => ['nullable', 'boolean'],
            'sms_pickup_reminder_days' => ['required', 'integer', 'min:0', 'max:14'],
            'sms_pickup_reminder_template' => ['required', 'string', 'max:480'],
            'sms_low_stock_owner_enabled' => ['nullable', 'boolean'],
            'sms_low_stock_owner_template' => ['required', 'string', 'max:480'],
        ]);

        $this->settings->set('sms_enabled', $request->boolean('sms_enabled') ? '1' : '0');
        $this->settings->set('sms_driver', $validated['sms_driver']);

        if (filled($validated['sms_access_token'] ?? '')) {
            $this->settings->set('sms_access_token', $validated['sms_access_token']);
        }

        if (filled($validated['sms_api_key'] ?? '')) {
            $this->settings->set('sms_api_key', $validated['sms_api_key']);
        }

        if (filled($validated['sms_secret_key'] ?? '')) {
            $this->settings->set('sms_secret_key', $validated['sms_secret_key']);
        }

        $this->settings->set('sms_sender_id', $validated['sms_sender_id'] ?? '');
        $this->settings->set('sms_cake_point_template', $validated['sms_cake_point_template']);
        $this->settings->set('sms_sale_confirmation_template', $validated['sms_sale_confirmation_template']);
        $this->settings->set('sms_cake_ready_template', $validated['sms_cake_ready_template']);
        $this->settings->set('sms_birthday_enabled', $request->boolean('sms_birthday_enabled') ? '1' : '0');
        $this->settings->set('sms_birthday_reminder_days', (string) $validated['sms_birthday_reminder_days']);
        $this->settings->set('sms_birthday_wish_template', $validated['sms_birthday_wish_template']);
        $this->settings->set('sms_birthday_reminder_template', $validated['sms_birthday_reminder_template']);
        $this->settings->set('sms_day_close_owner_enabled', $request->boolean('sms_day_close_owner_enabled') ? '1' : '0');
        $this->settings->set('sms_day_close_owner_template', $validated['sms_day_close_owner_template']);
        $this->settings->set('sms_pickup_reminder_enabled', $request->boolean('sms_pickup_reminder_enabled') ? '1' : '0');
        $this->settings->set('sms_pickup_reminder_days', (string) $validated['sms_pickup_reminder_days']);
        $this->settings->set('sms_pickup_reminder_template', $validated['sms_pickup_reminder_template']);
        $this->settings->set('sms_low_stock_owner_enabled', $request->boolean('sms_low_stock_owner_enabled') ? '1' : '0');
        $this->settings->set('sms_low_stock_owner_template', $validated['sms_low_stock_owner_template']);

        return redirect()
            ->route('settings.index', ['tab' => 'sms'])
            ->with('success', 'SMS settings saved successfully.');
    }
}
