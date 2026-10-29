<?php

namespace App\Console\Commands;

use App\Services\BirthdaySmsService;
use Illuminate\Console\Command;

class ProcessBirthdaySmsCommand extends Command
{
    protected $signature = 'sms:process-birthdays';

    protected $description = 'Send birthday wish and cake reminder SMS to customers';

    public function handle(BirthdaySmsService $birthdays): int
    {
        $result = $birthdays->processDue();

        $this->info("Birthday wishes sent: {$result['wishes']}");
        $this->info("Birthday reminders sent: {$result['reminders']}");

        return self::SUCCESS;
    }
}
