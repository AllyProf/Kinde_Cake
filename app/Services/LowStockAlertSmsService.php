<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LowStockAlertSmsService
{
    public function __construct(
        private SmsService $sms,
        private AppSettingsService $settings,
    ) {}

    public function isEnabled(): bool
    {
        return $this->settings->get('sms_low_stock_owner_enabled', '0') === '1'
            && $this->sms->isReady();
    }

    public function processDue(): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $today = now()->toDateString();
        if ($this->settings->get('sms_low_stock_last_sent_date') === $today) {
            return false;
        }

        $lowStock = Ingredient::query()
            ->with(['usagePackageUnit'])
            ->where('is_active', true)
            ->get()
            ->filter(fn (Ingredient $ingredient) => $ingredient->isLowStock() || (float) $ingredient->stock_quantity <= 0)
            ->values();

        if ($lowStock->isEmpty()) {
            return false;
        }

        $owner = User::query()
            ->where('role', User::ROLE_OWNER)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if (! $owner) {
            return false;
        }

        $phone = $this->sms->normalizePhone($owner->phone)
            ?: $this->sms->normalizePhone((string) $this->settings->get('business_phone', ''));

        if (! $phone) {
            return false;
        }

        $list = $lowStock
            ->take(8)
            ->map(fn (Ingredient $ingredient) => $ingredient->name.' ('.$ingredient->formattedStockSummary().')')
            ->implode('; ');

        if ($lowStock->count() > 8) {
            $list .= '; +'.($lowStock->count() - 8).' more';
        }

        $template = $this->settings->get(
            'sms_low_stock_owner_template',
            config('sms.defaults.low_stock_owner_template'),
        ) ?? config('sms.defaults.low_stock_owner_template');

        $message = str_replace(
            ['{business_name}', '{low_stock_count}', '{low_stock_list}'],
            [
                $this->settings->get('business_name', config('app.name', 'Kinde Cake')) ?? 'Kinde Cake',
                (string) $lowStock->count(),
                $list,
            ],
            $template,
        );

        $smsMessage = SmsMessage::create([
            'created_by_user_id' => $owner->id,
            'recipient_phone' => $phone,
            'recipient_name' => $owner->name,
            'recipient_type' => SmsMessage::RECIPIENT_OWNER,
            'recipient_id' => $owner->id,
            'type' => SmsMessage::TYPE_LOW_STOCK_ALERT,
            'message' => $message,
            'status' => SmsMessage::STATUS_SENDING,
            'attempts' => 1,
        ]);

        try {
            $response = $this->sms->send($phone, $message);
            $smsMessage->update([
                'status' => SmsMessage::STATUS_SENT,
                'sent_at' => now(),
                'provider' => $this->sms->currentDriver(),
                'provider_response' => $response,
                'last_error' => null,
            ]);
            $this->settings->set('sms_low_stock_last_sent_date', $today);

            return true;
        } catch (\Throwable $exception) {
            $smsMessage->update([
                'status' => SmsMessage::STATUS_FAILED,
                'last_error' => $exception->getMessage(),
            ]);

            Log::warning('Low stock owner SMS failed', ['error' => $exception->getMessage()]);

            return false;
        }
    }
}
