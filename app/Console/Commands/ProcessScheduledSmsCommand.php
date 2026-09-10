<?php

namespace App\Console\Commands;

use App\Services\SmsMessageService;
use Illuminate\Console\Command;

class ProcessScheduledSmsCommand extends Command
{
    protected $signature = 'sms:process-scheduled';

    protected $description = 'Send SMS messages that are scheduled for now or earlier';

    public function handle(SmsMessageService $smsMessages): int
    {
        $count = $smsMessages->processDueScheduled();

        if ($count > 0) {
            $this->info("Processed {$count} scheduled SMS message(s).");
        }

        return self::SUCCESS;
    }
}
