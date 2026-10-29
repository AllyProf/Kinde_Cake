<?php

namespace App\Services;

use App\Models\DayClose;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class DayCloseOwnerSmsService
{
    public function __construct(
        private SmsService $sms,
        private AppSettingsService $settings,
    ) {}

    public function isEnabled(): bool
    {
        return $this->settings->get('sms_day_close_owner_enabled', '1') === '1'
            && $this->sms->isReady();
    }

    public function notify(DayClose $dayClose): bool
    {
        if (! $this->isEnabled()) {
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
            Log::info('Day close owner SMS skipped: no owner/business phone.');

            return false;
        }

        $message = $this->buildMessage($dayClose);

        $smsMessage = SmsMessage::create([
            'created_by_user_id' => $dayClose->closed_by_user_id ?? $owner->id,
            'recipient_phone' => $phone,
            'recipient_name' => $owner->name,
            'recipient_type' => SmsMessage::RECIPIENT_OWNER,
            'recipient_id' => $owner->id,
            'type' => SmsMessage::TYPE_DAY_CLOSE_SUMMARY,
            'message' => $message,
            'status' => SmsMessage::STATUS_SENDING,
            'attempts' => 1,
            'related_type' => DayClose::class,
            'related_id' => $dayClose->id,
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

            return true;
        } catch (\Throwable $exception) {
            $smsMessage->update([
                'status' => SmsMessage::STATUS_FAILED,
                'last_error' => $exception->getMessage(),
            ]);

            Log::warning('Day close owner SMS failed', [
                'day_close_id' => $dayClose->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function buildMessage(DayClose $dayClose): string
    {
        $template = $this->settings->get(
            'sms_day_close_owner_template',
            config('sms.defaults.day_close_owner_template'),
        ) ?? config('sms.defaults.day_close_owner_template');

        $summary = $dayClose->summary ?? [];
        $money = fn (string $key): string => number_format((float) ($summary[$key] ?? 0), 0).' TZS';

        $replacements = [
            '{business_name}' => $this->settings->get('business_name', config('app.name', 'Kinde Cake')) ?? 'Kinde Cake',
            '{business_date}' => $dayClose->business_date?->format('d M Y') ?? '',
            '{total_collected}' => $money('total_collected'),
            '{expenses_total}' => $money('expenses_total'),
            '{net_in_hand}' => $money('net_in_hand'),
            '{cash_collected}' => $money('cash_collected'),
            '{mobile_collected}' => $money('mobile_collected'),
            '{bank_collected}' => $money('bank_collected'),
            '{sales_count}' => (string) ((int) ($summary['sales_count'] ?? 0)),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}
