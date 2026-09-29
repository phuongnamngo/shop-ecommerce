<?php

namespace App\Console\Commands;

use App\Support\OpsAlert;
use Illuminate\Console\Command;

final class OpsAlertTestCommand extends Command
{
    protected $signature = 'ops:alert-test';

    protected $description = 'Send a test message through OpsAlert';

    public function handle(): int
    {
        OpsAlert::send('ops alert test');
        $this->info('ops alert test sent');

        return self::SUCCESS;
    }
}
