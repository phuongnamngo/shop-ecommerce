<?php

namespace App\Services\Shipping;

final readonly class GhnWebhookEvent
{
    public function __construct(
        public string $orderCode,
        public string $rawStatus,
        public ?string $tracking,
    ) {}
}
