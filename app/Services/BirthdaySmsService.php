<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class BirthdaySmsService
{
    public function __construct(
        private SmsService $sms,
        private AppSettingsService $settings,
    ) {}

    public function isEnabled(): bool
    {
        return $this->settings->get('sms_birthday_enabled', '1') === '1'
            && $this->sms->isReady();
    }

    public function reminderDays(): int
    {
        $days = (int) ($this->settings->get(
            'sms_birthday_reminder_days',
            (string) config('sms.defaults.birthday_reminder_days', 7),
        ) ?? 7);

        return max(1, min(30, $days));
    }

    /** @return array{wishes: int, reminders: int} */
    public function processDue(): array
    {
        if (! $this->isEnabled()) {
            return ['wishes' => 0, 'reminders' => 0];
        }

        $owner = User::query()->where('role', User::ROLE_OWNER)->orderBy('id')->first();

        if (! $owner) {
            return ['wishes' => 0, 'reminders' => 0];
        }

        $today = now()->startOfDay();
        $year = (int) $today->year;
        $wishes = 0;
        $reminders = 0;
        $reminderDays = $this->reminderDays();

        Customer::query()
            ->where('is_active', true)
            ->whereNotNull('birthday')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id')
            ->each(function (Customer $customer) use ($owner, $today, $year, $reminderDays, &$wishes, &$reminders) {
                if ($customer->hasBirthdayToday($today) && (int) $customer->birthday_wish_sent_year !== $year) {
                    if ($this->sendWish($owner, $customer)) {
                        $customer->update(['birthday_wish_sent_year' => $year]);
                        $wishes++;
                    }
                }

                $daysUntil = $customer->daysUntilBirthday($today);
                if ($daysUntil === $reminderDays && (int) $customer->birthday_reminder_sent_year !== $year) {
                    if ($this->sendReminder($owner, $customer)) {
                        $customer->update(['birthday_reminder_sent_year' => $year]);
                        $reminders++;
                    }
                }
            });

        return ['wishes' => $wishes, 'reminders' => $reminders];
    }

    public function buildWishMessage(Customer $customer): string
    {
        $template = $this->settings->get(
            'sms_birthday_wish_template',
            config('sms.defaults.birthday_wish_template'),
        ) ?? config('sms.defaults.birthday_wish_template');

        return $this->replacePlaceholders($template, $customer);
    }

    public function buildReminderMessage(Customer $customer): string
    {
        $template = $this->settings->get(
            'sms_birthday_reminder_template',
            config('sms.defaults.birthday_reminder_template'),
        ) ?? config('sms.defaults.birthday_reminder_template');

        return $this->replacePlaceholders($template, $customer);
    }

    private function replacePlaceholders(string $template, Customer $customer): string
    {
        $replacements = [
            '{customer_name}' => $customer->name,
            '{birthday_date}' => $customer->birthday?->format('d M') ?? '',
            '{business_name}' => $this->settings->get('business_name', config('app.name', 'Kinde Cake')) ?? 'Kinde Cake',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function sendWish(User $owner, Customer $customer): bool
    {
        return $this->deliver(
            $owner,
            $customer,
            $this->buildWishMessage($customer),
            SmsMessage::TYPE_BIRTHDAY_WISH,
        );
    }

    private function sendReminder(User $owner, Customer $customer): bool
    {
        return $this->deliver(
            $owner,
            $customer,
            $this->buildReminderMessage($customer),
            SmsMessage::TYPE_BIRTHDAY_REMINDER,
        );
    }

    private function deliver(User $owner, Customer $customer, string $message, string $type): bool
    {
        $phone = $this->sms->normalizePhone($customer->phone);

        if (! $phone) {
            return false;
        }

        $smsMessage = SmsMessage::create([
            'created_by_user_id' => $owner->id,
            'recipient_phone' => $phone,
            'recipient_name' => $customer->name,
            'recipient_type' => SmsMessage::RECIPIENT_CUSTOMER,
            'recipient_id' => $customer->id,
            'type' => $type,
            'message' => $message,
            'status' => SmsMessage::STATUS_SENDING,
            'attempts' => 1,
            'related_type' => Customer::class,
            'related_id' => $customer->id,
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

            Log::warning('Birthday SMS failed', [
                'customer_id' => $customer->id,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
