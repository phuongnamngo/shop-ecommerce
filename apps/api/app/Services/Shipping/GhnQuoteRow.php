<?php

namespace App\Services\Shipping;

final readonly class GhnQuoteRow
{
    public function __construct(
        public int $serviceId,
        public string $name,
        public int $fee,
    ) {}
}
