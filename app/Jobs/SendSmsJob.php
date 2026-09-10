<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Services\SmsMessageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public SmsMessage $smsMessage) {}

    public function handle(SmsMessageService $smsMessages): void
    {
        $this->smsMessage->refresh();

        $smsMessages->deliver($this->smsMessage);
    }
}
