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
        if ($this->settings->get('sms_enabled', '0') === '1') {
            return true;
        }

        $envFlag = config('sms.enabled');

        if ($envFlag !== null && $envFlag !== '') {
            return filter_var($envFlag, FILTER_VALIDATE_BOOLEAN);
        }

        return false;
    }

    public function isConfigured(): bool
    {
        $driver = $this->driver();

        if ($driver === 'log') {
            return true;
        }

        if ($driver === 'mauzo_link') {
            return filled($this->accessToken()) && filled($this->senderId());
        }

        return filled($this->settings->get('sms_api_key'))
            && filled($this->settings->get('sms_secret_key'))
            && filled($this->settings->get('sms_sender_id'));
    }

    public function isReady(): bool
    {
        return $this->isEnabled() && $this->isConfigured();
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

    /** @return string|null Provider response body on success (when available). */
    public function send(string $phone, string $message): ?string
    {
        $normalizedPhone = $this->normalizePhone($phone);

        if (! $normalizedPhone) {
            throw ValidationException::withMessages([
                'phone' => 'The phone number is invalid.',
            ]);
        }

        return match ($this->driver()) {
            'log' => $this->sendViaLog($normalizedPhone, $message),
            'mauzo_link' => $this->sendViaMauzoLink($normalizedPhone, $message),
            'beem' => $this->sendViaBeem($normalizedPhone, $message),
            default => throw ValidationException::withMessages([
                'sms' => 'Unsupported SMS provider.',
            ]),
        };
    }

    public function tokenConfiguredInEnvironment(): bool
    {
        return filled(config('sms.mauzo_link.token'));
    }

    public function normalizePhone(?string $phone): ?string
    {
        if (! filled($phone)) {
            return null;
        }

        $trimmed = trim($phone);
        $hadPlus = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D+/', '', $trimmed);

        if (! $digits) {
            return null;
        }

        if ($hadPlus && strlen($digits) < 7) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '255'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '255')) {
            $digits = '255'.$digits;
        }

        if (! preg_match('/^255[67]\d{8}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    private function driver(): string
    {
        return $this->settings->get('sms_driver', config('sms.driver', 'mauzo_link')) ?? 'mauzo_link';
    }

    private function accessToken(): ?string
    {
        foreach ([
            $this->settings->get('sms_access_token'),
            $this->settings->get('sms_secret_key'),
        ] as $candidate) {
            if (filled($candidate)) {
                return $candidate;
            }
        }

        return config('sms.mauzo_link.token');
    }

    private function senderId(): ?string
    {
        $fromSettings = $this->settings->get('sms_sender_id');

        if (filled($fromSettings)) {
            return $fromSettings;
        }

        return config('sms.mauzo_link.sender_id');
    }

    public function currentDriver(): string
    {
        return $this->driver();
    }

    private function sendViaLog(string $phone, string $message): ?string
    {
        Log::info('SMS (log driver)', [
            'to' => $phone,
            'message' => $message,
        ]);

        return null;
    }

    private function sendViaMauzoLink(string $phone, string $message): ?string
    {
        $token = $this->accessToken();
        $senderId = $this->senderId();

        $transport = config('sms.mauzo_link.transport', 'api');

        $response = $transport === 'link'
            ? $this->sendMauzoLinkViaLinkApi($token, $senderId, $phone, $message)
            : $this->sendMauzoLinkViaJsonApi($token, $senderId, $phone, $message);

        if ($response->status() !== 200) {
            throw ValidationException::withMessages([
                'sms' => $this->formatProviderError($response->status(), $response->body()),
            ]);
        }

        $body = trim((string) $response->body());
        $this->assertMauzoLinkMessageAccepted($body);

        return $body !== '' ? $body : null;
    }

    private function assertMauzoLinkMessageAccepted(string $body): void
    {
        if ($body === '') {
            return;
        }

        $payload = json_decode($body, true);

        if (! is_array($payload)) {
            return;
        }

        $groupName = $payload['messages'][0]['status']['groupName'] ?? null;
        $statusName = $payload['messages'][0]['status']['name'] ?? null;
        $description = $payload['messages'][0]['status']['description'] ?? null;

        if (! in_array($groupName, ['FAILED', 'REJECTED'], true)) {
            return;
        }

        $error = trim(($statusName ?: 'Send failed').($description ? ': '.$description : ''));

        throw ValidationException::withMessages([
            'sms' => $error !== '' ? $error : 'SMS was rejected by the provider.',
        ]);
    }

    private function formatProviderError(int $status, ?string $body): string
    {
        $error = 'SMS could not be sent (HTTP '.$status.').';
        $body = trim((string) $body);

        if ($body === '') {
            return $error;
        }

        $payload = json_decode($body, true);

        if (is_array($payload)) {
            $message = $payload['error'] ?? $payload['message'] ?? null;

            if (is_string($message) && $message !== '') {
                return $error.' '.$message;
            }
        }

        return $error.' '.$body;
    }

    private function sendMauzoLinkViaJsonApi(string $token, string $senderId, string $phone, string $message): \Illuminate\Http\Client\Response
    {
        return Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->post(config('sms.mauzo_link.api_endpoint'), [
                'from' => $senderId,
                'to' => $phone,
                'text' => $message,
                'flash' => 0,
            ]);
    }

    private function sendMauzoLinkViaLinkApi(string $token, string $senderId, string $phone, string $message): \Illuminate\Http\Client\Response
    {
        return Http::timeout(30)->get(config('sms.mauzo_link.link_endpoint'), [
            'token' => $token,
            'from' => $senderId,
            'to' => $phone,
            'text' => $message,
        ]);
    }

    private function sendViaBeem(string $phone, string $message): ?string
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

        return $response->body();
    }
}
