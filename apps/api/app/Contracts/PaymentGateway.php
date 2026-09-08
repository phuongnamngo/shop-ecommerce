<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payment\VnPayVerificationResult;

interface PaymentGateway
{
    /**
     * @return array{redirect_url: string|null}
     */
    public function initiate(Order $order, PaymentTransaction $txn): array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function verify(array $payload): VnPayVerificationResult;
}
