<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CakePointService
{
    public function __construct(
        private SmsService $sms,
        private SmsMessageService $smsMessages,
        private SaleService $sales,
    ) {}

    /**
     * @param  array<int, array{item_id: int, quantity: float}>  $items
     * @param  array{
     *     ready_at?: ?string,
     *     customer_birthday?: ?string,
     *     advance_amount?: float|null,
     *     payment_method?: ?string,
     *     payment_provider_id?: ?int,
     *     payment_reference?: ?string,
     *     credit_repayment_date?: ?string
     * }  $options
     */
    public function createAndAssign(
        User $owner,
        User $staff,
        array $items,
        ?int $customerId,
        ?string $customerName,
        ?string $customerPhone,
        ?string $notes,
        bool $sendSms,
        array $options = [],
    ): Sale {
        if (! $owner->isOwner()) {
            abort(403, 'Only the owner can send orders to the cake point.');
        }

        $this->assertStaffMember($staff);

        if (! filled($customerPhone)) {
            throw ValidationException::withMessages([
                'customer_phone' => 'Please enter the customer phone number.',
            ]);
        }

        $customerId = $this->syncCustomerBirthday(
            $customerId,
            $customerName,
            $customerPhone,
            $options['customer_birthday'] ?? null,
        );

        $sale = DB::transaction(function () use ($owner, $staff, $items, $customerId, $customerName, $customerPhone, $notes, $options) {
            $sale = $this->sales->createCakePointOrder(
                $owner,
                $items,
                $customerId,
                $customerName,
                $customerPhone,
                $notes,
                $options['ready_at'] ?? null,
            );

            $sale->update($this->assignmentPayload($staff, $owner));

            return $sale->fresh(['items.item']);
        });

        $advanceAmount = (float) ($options['advance_amount'] ?? 0);
        if ($advanceAmount > 0) {
            $readyAt = $options['ready_at'] ?? null;
            $balanceDueDate = $options['credit_repayment_date']
                ?? ($readyAt ? \Carbon\Carbon::parse($readyAt)->toDateString() : now()->toDateString());

            $this->sales->pay(
                $owner,
                $sale,
                $advanceAmount,
                $options['payment_method'] ?? Sale::PAYMENT_CASH,
                isset($options['payment_provider_id']) ? (int) $options['payment_provider_id'] : null,
                $options['payment_reference'] ?? null,
                $customerId,
                $customerName,
                $customerPhone,
                $balanceDueDate,
            );
            $sale->refresh();
        }

        if ($sendSms) {
            $this->notifyStaff($owner, $sale, $staff);
            $this->notifyCustomer($owner, $sale);
        }

        return $sale->fresh(['assignedTo', 'assignedBy', 'items.item', 'user', 'payments']);
    }

    private function syncCustomerBirthday(
        ?int $customerId,
        ?string $customerName,
        ?string $customerPhone,
        ?string $birthday,
    ): ?int {
        if (! filled($birthday)) {
            return $customerId;
        }

        if ($customerId) {
            Customer::query()->whereKey($customerId)->update(['birthday' => $birthday]);

            return $customerId;
        }

        if (! filled($customerPhone)) {
            return null;
        }

        $customer = Customer::query()->where('phone', $customerPhone)->first();

        if ($customer) {
            $customer->update([
                'birthday' => $birthday,
                'name' => filled($customerName) ? $customerName : $customer->name,
            ]);

            return $customer->id;
        }

        if (! filled($customerName)) {
            return null;
        }

        $customer = Customer::create([
            'name' => $customerName,
            'phone' => $customerPhone,
            'birthday' => $birthday,
            'is_active' => true,
        ]);

        return $customer->id;
    }

    public function assign(Sale $sale, User $owner, User $staff, bool $sendSms = false): Sale
    {
        if (! $owner->isOwner()) {
            abort(403, 'Only the owner can send orders to the cake point.');
        }

        $this->assertStaffMember($staff);

        if ($sale->isVoided()) {
            throw ValidationException::withMessages([
                'sale' => 'Cannot assign a cancelled or deleted order.',
            ]);
        }

        $sale = DB::transaction(function () use ($sale, $owner, $staff) {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            $sale->update($this->assignmentPayload($staff, $owner));

            return $sale->fresh(['items.item']);
        });

        if ($sendSms) {
            $this->notifyStaff($owner, $sale, $staff);
            $this->notifyCustomer($owner, $sale);
        }

        return $sale->fresh(['assignedTo', 'assignedBy', 'items.item', 'user']);
    }

    public function updateStatus(Sale $sale, User $user, string $status): Sale
    {
        if (! $sale->canUpdateCakePointStatus($user)) {
            abort(403, 'You cannot update this cake point order.');
        }

        $allowed = match ($sale->cake_point_status) {
            Sale::CAKE_POINT_SENT => [Sale::CAKE_POINT_RECEIVED],
            Sale::CAKE_POINT_RECEIVED => [Sale::CAKE_POINT_PREPARED],
            default => [],
        };

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status change for this order.',
            ]);
        }

        $updates = ['cake_point_status' => $status];

        if ($status === Sale::CAKE_POINT_RECEIVED) {
            $updates['cake_point_received_at'] = now();
        }

        if ($status === Sale::CAKE_POINT_PREPARED) {
            $updates['cake_point_prepared_at'] = now();
        }

        $sale->update($updates);

        return $sale->fresh(['assignedTo', 'assignedBy', 'items.item', 'user']);
    }

    public function completeFromSale(Sale $cakePointSale, Sale $newSale): Sale
    {
        if ($cakePointSale->isCakePointCompleted()) {
            return $cakePointSale;
        }

        $cakePointSale->update([
            'cake_point_status' => Sale::CAKE_POINT_COMPLETED,
            'cake_point_completed_at' => now(),
            'converted_to_sale_id' => $newSale->id,
        ]);

        return $cakePointSale->fresh();
    }

    /** @return array<string, mixed> */
    private function assignmentPayload(User $staff, User $owner): array
    {
        return [
            'assigned_to_user_id' => $staff->id,
            'assigned_by_user_id' => $owner->id,
            'assigned_at' => now(),
            'cake_point_status' => Sale::CAKE_POINT_SENT,
            'cake_point_received_at' => null,
            'cake_point_prepared_at' => null,
            'cake_point_completed_at' => null,
            'converted_to_sale_id' => null,
        ];
    }

    private function assertStaffMember(User $staff): void
    {
        if ($staff->role !== User::ROLE_STAFF || ! $staff->is_active) {
            throw ValidationException::withMessages([
                'staff_id' => 'Please select an active staff member.',
            ]);
        }
    }

    private function notifyStaff(User $sender, Sale $sale, User $staff): void
    {
        if (! filled($staff->phone)) {
            throw ValidationException::withMessages([
                'phone' => 'Selected staff member has no valid phone number for SMS.',
            ]);
        }

        if (! $this->sms->isReady()) {
            throw ValidationException::withMessages([
                'sms' => 'SMS is not enabled or configured. Set it up in Settings → SMS, or uncheck notify by phone.',
            ]);
        }

        $message = $this->sms->buildCakePointMessage($sale, $staff);

        $smsMessage = $this->smsMessages->create(
            $sender,
            $staff->phone,
            $message,
            $staff->name,
            null,
            SmsMessage::TYPE_CAKE_POINT,
            SmsMessage::RECIPIENT_STAFF,
            $staff->id,
            $sale,
            true,
        );

        if ($smsMessage->status === SmsMessage::STATUS_SENT) {
            $sale->update(['assignment_sms_sent_at' => now()]);
        }
    }

    private function notifyCustomer(User $sender, Sale $sale): void
    {
        if (! filled($sale->customer_phone)) {
            return;
        }

        if (! $this->sms->isReady()) {
            throw ValidationException::withMessages([
                'sms' => 'SMS is not enabled or configured. Set it up in Settings → SMS, or uncheck notify by phone.',
            ]);
        }

        $sale->loadMissing(['items.item']);
        $message = $this->sms->buildSaleConfirmationMessage($sale);

        $this->smsMessages->create(
            $sender,
            $sale->customer_phone,
            $message,
            $sale->customer_name,
            null,
            SmsMessage::TYPE_SALE_CONFIRMATION,
            SmsMessage::RECIPIENT_CUSTOMER,
            $sale->customer_id,
            $sale,
            true,
        );
    }
}
