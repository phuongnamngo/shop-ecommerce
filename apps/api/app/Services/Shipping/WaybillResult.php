<?php

namespace App\Services\Shipping;

final readonly class WaybillResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public bool $ok,
        public ?string $trackingNumber,
        public array $payload,
    ) {}
}
