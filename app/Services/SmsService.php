<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SmsService
{
    public function __construct(private AppSettingsService $settings) {}

    public function isEnabled(): bool
    {
        return $this->settings->get('sms_enabled', '0') === '1';
    }

    public function isConfigured(): bool
    {
        $driver = $this->driver();

        if ($driver === 'log') {
            return true;
        }

        return filled($this->settings->get('sms_api_key'))
            && filled($this->settings->get('sms_secret_key'))
            && filled($this->settings->get('sms_sender_id'));
    }

    public function isReady(): bool
    {
        return $this->isEnabled() && $this->isConfigured();
    }

    public function sendOrderToStaff(Sale $sale, User $staff): void
    {
        $phone = $this->normalizePhone($staff->phone);

        if (! $phone) {
            throw ValidationException::withMessages([
                'phone' => 'Selected staff member has no valid phone number for SMS.',
            ]);
        }

        if (! $this->isReady()) {
            throw ValidationException::withMessages([
                'sms' => 'SMS is not enabled or configured. Set it up in Settings → SMS.',
            ]);
        }

        $this->send($phone, $this->buildCakePointMessage($sale, $staff));
    }

    public function buildCakePointMessage(Sale $sale, User $staff): string
    {
        $template = $this->settings->get(
            'sms_cake_point_template',
            config('sms.defaults.cake_point_template'),
        ) ?? config('sms.defaults.cake_point_template');

        $replacements = [
            '{staff_name}' => $staff->name,
            '{sale_number}' => $sale->sale_number,
            '{customer_name}' => $sale->customer_name ?: 'Walk-in customer',
            '{customer_phone}' => $sale->customer_phone ?: '—',
            '{items_summary}' => $sale->itemsSummary() ?: '—',
            '{total}' => $sale->formattedTotal(),
            '{business_name}' => $this->settings->get('business_name', config('app.name', 'Kinde Cake')) ?? 'Kinde Cake',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    public function previewCakePointMessage(Sale $sale, User $staff): string
    {
        return $this->buildCakePointMessage($sale, $staff);
    }

    public function send(string $phone, string $message): void
    {
        $normalizedPhone = $this->normalizePhone($phone);

        if (! $normalizedPhone) {
            throw ValidationException::withMessages([
                'phone' => 'The phone number is invalid.',
            ]);
        }

        match ($this->driver()) {
            'log' => $this->sendViaLog($normalizedPhone, $message),
            'beem' => $this->sendViaBeem($normalizedPhone, $message),
            default => throw ValidationException::withMessages([
                'sms' => 'Unsupported SMS provider.',
            ]),
        };
    }

    public function normalizePhone(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (! $digits) {
            return null;
        }

        if (str_starts_with($digits, '255')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (! preg_match('/^[67]\d{8}$/', $digits)) {
            return null;
        }

        return '255'.$digits;
    }

    private function driver(): string
    {
        return $this->settings->get('sms_driver', config('sms.driver', 'beem')) ?? 'beem';
    }

    public function currentDriver(): string
    {
        return $this->driver();
    }

    private function sendViaLog(string $phone, string $message): void
    {
        Log::info('SMS (log driver)', [
            'to' => $phone,
            'message' => $message,
        ]);
    }

    private function sendViaBeem(string $phone, string $message): void
    {
        $apiKey = $this->settings->get('sms_api_key');
        $secretKey = $this->settings->get('sms_secret_key');
        $senderId = $this->settings->get('sms_sender_id');

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Basic '.base64_encode($apiKey.':'.$secretKey),
                'Content-Type' => 'application/json',
            ])
            ->post(config('sms.beem.endpoint'), [
                'source_addr' => $senderId,
                'encoding' => 0,
                'schedule_time' => '',
                'message' => $message,
                'recipients' => [
                    [
                        'recipient_id' => 1,
                        'dest_addr' => $phone,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw ValidationException::withMessages([
                'sms' => 'SMS provider error: '.$response->body(),
            ]);
        }

        $payload = $response->json();

        if (is_array($payload) && isset($payload['code']) && (int) $payload['code'] !== 100) {
            throw ValidationException::withMessages([
                'sms' => $payload['message'] ?? 'SMS could not be sent.',
            ]);
        }
    }
}
