<?php

namespace App\Console\Commands;

use App\Services\Order\OrderService;
use Illuminate\Console\Command;

final class ExpirePendingOrdersCommand extends Command
{
    protected $signature = 'commerce:expire-pending-orders';

    protected $description = 'Cancel pending orders whose stock reservations have expired';

    public function handle(OrderService $orders): int
    {
        $count = $orders->cancelExpiredPending();
        $this->info("Expired {$count} pending order(s).");

        return self::SUCCESS;
    }
}
