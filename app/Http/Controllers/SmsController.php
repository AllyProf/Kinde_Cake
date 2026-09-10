<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\SmsMessageService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SmsController extends Controller
{
    public function __construct(
        private SmsMessageService $smsMessages,
        private SmsService $sms,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in([
                SmsMessage::STATUS_PENDING,
                SmsMessage::STATUS_SENT,
                SmsMessage::STATUS_FAILED,
                SmsMessage::STATUS_CANCELLED,
            ])],
        ]);

        $messages = SmsMessage::query()
            ->with('createdBy:id,name')
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $customers = Customer::query()
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $staffMembers = User::query()
            ->where('role', User::ROLE_STAFF)
            ->where('is_active', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('sms.index', [
            'messages' => $messages,
            'customers' => $customers,
            'staffMembers' => $staffMembers,
            'smsReady' => $this->sms->isReady(),
            'statusFilter' => $validated['status'] ?? null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_type' => ['required', Rule::in([
                SmsMessage::RECIPIENT_MANUAL,
                SmsMessage::RECIPIENT_CUSTOMER,
                SmsMessage::RECIPIENT_STAFF,
            ])],
            'phone' => ['nullable', 'string', 'max:30'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'staff_id' => ['nullable', 'exists:users,id'],
            'message' => ['required', 'string', 'max:480'],
            'send_option' => ['required', Rule::in(['now', 'schedule'])],
            'scheduled_at' => ['nullable', 'required_if:send_option,schedule', 'date', 'after:now'],
        ]);

        [$phone, $recipientName, $recipientType, $recipientId] = $this->resolveRecipient($validated);

        $scheduledAt = $validated['send_option'] === 'schedule'
            ? Carbon::parse($validated['scheduled_at'])
            : null;

        $sendImmediately = $validated['send_option'] === 'now';

        try {
            $this->smsMessages->create(
                $request->user(),
                $phone,
                $validated['message'],
                $recipientName,
                $scheduledAt,
                SmsMessage::TYPE_MANUAL,
                $recipientType,
                $recipientId,
                null,
                $sendImmediately,
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()
                ->route('sms.index')
                ->withInput()
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        $successMessage = $sendImmediately
            ? 'SMS sent successfully.'
            : 'SMS scheduled for '.$scheduledAt->format('d M Y H:i').'.';

        return redirect()
            ->route('sms.index')
            ->with('success', $successMessage);
    }

    public function destroy(Request $request, SmsMessage $sms): RedirectResponse
    {
        try {
            $this->smsMessages->cancel($request->user(), $sms);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return redirect()
                ->route('sms.index')
                ->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()
            ->route('sms.index')
            ->with('success', 'Scheduled SMS cancelled.');
    }

    /** @param  array<string, mixed>  $validated */
    private function resolveRecipient(array $validated): array
    {
        if ($validated['recipient_type'] === SmsMessage::RECIPIENT_CUSTOMER) {
            $customer = Customer::query()->find($validated['customer_id'] ?? null);

            if (! $customer || ! filled($customer->phone)) {
                throw ValidationException::withMessages([
                    'customer_id' => 'Please select a customer with a phone number.',
                ]);
            }

            return [$customer->phone, $customer->name, SmsMessage::RECIPIENT_CUSTOMER, $customer->id];
        }

        if ($validated['recipient_type'] === SmsMessage::RECIPIENT_STAFF) {
            $staff = User::query()
                ->where('role', User::ROLE_STAFF)
                ->find($validated['staff_id'] ?? null);

            if (! $staff || ! filled($staff->phone)) {
                throw ValidationException::withMessages([
                    'staff_id' => 'Please select a staff member with a phone number.',
                ]);
            }

            return [$staff->phone, $staff->name, SmsMessage::RECIPIENT_STAFF, $staff->id];
        }

        if (! filled($validated['phone'] ?? null)) {
            throw ValidationException::withMessages([
                'phone' => 'Phone number is required.',
            ]);
        }

        return [$validated['phone'], null, SmsMessage::RECIPIENT_MANUAL, null];
    }
}
