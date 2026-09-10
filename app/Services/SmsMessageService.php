<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\SmsMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SmsMessageService
{
    public function __construct(private SmsService $sms) {}

    public function create(
        User $user,
        string $phone,
        string $message,
        ?string $recipientName = null,
        ?Carbon $scheduledAt = null,
        string $type = SmsMessage::TYPE_MANUAL,
        string $recipientType = SmsMessage::RECIPIENT_MANUAL,
        ?int $recipientId = null,
        ?Model $related = null,
        bool $sendImmediately = true,
    ): SmsMessage {
        if (! $user->canSendSms()) {
            abort(403, 'You do not have permission to send SMS.');
        }

        if (! $this->sms->isReady()) {
            throw ValidationException::withMessages([
                'sms' => 'SMS is not enabled or configured. Set it up in Settings → SMS.',
            ]);
        }

        $normalizedPhone = $this->sms->normalizePhone($phone);

        if (! $normalizedPhone) {
            throw ValidationException::withMessages([
                'phone' => 'The phone number is invalid.',
            ]);
        }

        $message = trim($message);

        if ($message === '') {
            throw ValidationException::withMessages([
                'message' => 'Message is required.',
            ]);
        }

        if (mb_strlen($message) > 480) {
            throw ValidationException::withMessages([
                'message' => 'Message must not exceed 480 characters.',
            ]);
        }

        if ($scheduledAt && $scheduledAt->isPast()) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Scheduled time must be in the future.',
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $normalizedPhone,
            $message,
            $recipientName,
            $scheduledAt,
            $type,
            $recipientType,
            $recipientId,
            $related,
            $sendImmediately,
        ) {
            $smsMessage = SmsMessage::create([
                'created_by_user_id' => $user->id,
                'recipient_phone' => $normalizedPhone,
                'recipient_name' => $recipientName,
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId,
                'type' => $type,
                'message' => $message,
                'status' => SmsMessage::STATUS_PENDING,
                'scheduled_at' => $scheduledAt,
                'related_type' => $related ? $related::class : null,
                'related_id' => $related?->getKey(),
            ]);

            if ($sendImmediately && (! $scheduledAt || $scheduledAt->lte(now()))) {
                SendSmsJob::dispatchSync($smsMessage);
                $smsMessage->refresh();
            }

            return $smsMessage;
        });
    }

    public function cancel(User $user, SmsMessage $message): void
    {
        if (! $user->canSendSms()) {
            abort(403, 'You do not have permission to cancel SMS.');
        }

        if (! $message->canBeCancelled()) {
            throw ValidationException::withMessages([
                'sms' => 'Only pending or scheduled messages can be cancelled.',
            ]);
        }

        $message->update([
            'status' => SmsMessage::STATUS_CANCELLED,
        ]);
    }

    public function processDueScheduled(): int
    {
        $processed = 0;

        SmsMessage::query()
            ->where('status', SmsMessage::STATUS_PENDING)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->each(function (SmsMessage $message) use (&$processed) {
                SendSmsJob::dispatchSync($message);
                $processed++;
            });

        return $processed;
    }

    public function deliver(SmsMessage $message): void
    {
        if ($message->status === SmsMessage::STATUS_CANCELLED) {
            return;
        }

        if ($message->status === SmsMessage::STATUS_SENT) {
            return;
        }

        $message->update([
            'status' => SmsMessage::STATUS_SENDING,
            'attempts' => $message->attempts + 1,
        ]);

        try {
            $this->sms->send($message->recipient_phone, $message->message);

            $message->update([
                'status' => SmsMessage::STATUS_SENT,
                'sent_at' => now(),
                'provider' => $this->sms->currentDriver(),
                'last_error' => null,
            ]);
        } catch (\Throwable $exception) {
            $message->update([
                'status' => SmsMessage::STATUS_FAILED,
                'last_error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
