<?php

namespace App\Console\Commands;

use App\Services\PickupReminderSmsService;
use Illuminate\Console\Command;

class ProcessPickupReminderSmsCommand extends Command
{
    protected $signature = 'sms:process-pickup-reminders';

    protected $description = 'Remind customers to collect cakes on/near ready date';

    public function handle(PickupReminderSmsService $reminders): int
    {
        $count = $reminders->processDue();
        $this->info("Pickup reminders sent: {$count}");

        return self::SUCCESS;
    }
}
