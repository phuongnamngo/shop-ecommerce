<?php

namespace App\Services\Payment;

final readonly class PaymentRefundResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public bool $ok,
        public ?string $providerRefundId,
        public array $payload,
    ) {}
}
