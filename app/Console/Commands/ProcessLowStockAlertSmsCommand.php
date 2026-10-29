<?php

namespace App\Console\Commands;

use App\Services\LowStockAlertSmsService;
use Illuminate\Console\Command;

class ProcessLowStockAlertSmsCommand extends Command
{
    protected $signature = 'sms:process-low-stock-alerts';

    protected $description = 'Send daily low-stock alert SMS to the owner when enabled';

    public function handle(LowStockAlertSmsService $alerts): int
    {
        $sent = $alerts->processDue();
        $this->info($sent ? 'Low stock alert sent.' : 'No low stock alert sent.');

        return self::SUCCESS;
    }
}
