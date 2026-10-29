<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class PickupReminderSmsService
{
    public function __construct(
        private SmsService $sms,
        private AppSettingsService $settings,
    ) {}

    public function isEnabled(): bool
    {
        return $this->settings->get('sms_pickup_reminder_enabled', '1') === '1'
            && $this->sms->isReady();
    }

    public function reminderDays(): int
    {
        $days = (int) ($this->settings->get(
            'sms_pickup_reminder_days',
            (string) config('sms.defaults.pickup_reminder_days', 1),
        ) ?? 1);

        return max(0, min(14, $days));
    }

    public function processDue(): int
    {
        if (! $this->isEnabled()) {
            return 0;
        }

        $owner = User::query()->where('role', User::ROLE_OWNER)->orderBy('id')->first();
        if (! $owner) {
            return 0;
        }

        $targetDate = now()->startOfDay()->addDays($this->reminderDays());
        $sent = 0;

        Sale::query()
            ->realSales()
            ->with(['items'])
            ->whereNotNull('ready_at')
            ->whereDate('ready_at', $targetDate->toDateString())
            ->whereNull('pickup_reminder_sent_at')
            ->whereNotNull('customer_phone')
            ->where('customer_phone', '!=', '')
            ->whereNotIn('status', [Sale::STATUS_CANCELLED, Sale::STATUS_DELETED])
            ->orderBy('id')
            ->each(function (Sale $sale) use ($owner, &$sent) {
                if ($this->sendReminder($owner, $sale)) {
                    $sale->update(['pickup_reminder_sent_at' => now()]);
                    $sent++;
                }
            });

        return $sent;
    }

    public function buildMessage(Sale $sale): string
    {
        $template = $this->settings->get(
            'sms_pickup_reminder_template',
            config('sms.defaults.pickup_reminder_template'),
        ) ?? config('sms.defaults.pickup_reminder_template');

        $readyAt = $sale->ready_at?->format('d M Y') ?? '';
        $readyAtLine = $readyAt !== '' ? ' Collection date: '.$readyAt.'.' : '';

        $replacements = [
            '{customer_name}' => $sale->customer_name ?: 'Customer',
            '{sale_number}' => $sale->sale_number,
            '{items_summary}' => $sale->itemsSummary() ?: '—',
            '{total}' => $sale->formattedTotal(),
            '{balance_due}' => number_format($sale->balanceDue(), 0).' TZS',
            '{ready_at}' => $readyAt,
            '{ready_at_line}' => $readyAtLine,
            '{business_name}' => $this->settings->get('business_name', config('app.name', 'Kinde Cake')) ?? 'Kinde Cake',
            '{business_phone}' => $this->settings->get('business_phone', '') ?: '—',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function sendReminder(User $owner, Sale $sale): bool
    {
        $phone = $this->sms->normalizePhone($sale->customer_phone);
        if (! $phone) {
            return false;
        }

        $message = $this->buildMessage($sale);

        $smsMessage = SmsMessage::create([
            'created_by_user_id' => $owner->id,
            'recipient_phone' => $phone,
            'recipient_name' => $sale->customer_name,
            'recipient_type' => SmsMessage::RECIPIENT_CUSTOMER,
            'recipient_id' => $sale->customer_id,
            'type' => SmsMessage::TYPE_PICKUP_REMINDER,
            'message' => $message,
            'status' => SmsMessage::STATUS_SENDING,
            'attempts' => 1,
            'related_type' => Sale::class,
            'related_id' => $sale->id,
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

            Log::warning('Pickup reminder SMS failed', [
                'sale_id' => $sale->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
