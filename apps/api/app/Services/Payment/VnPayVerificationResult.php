<?php

namespace App\Services\Payment;

final readonly class VnPayVerificationResult
{
    public function __construct(
        public bool $ok,
        public string $responseCode,
        public string $orderRef,
        public ?string $providerTxnId,
    ) {}
}
