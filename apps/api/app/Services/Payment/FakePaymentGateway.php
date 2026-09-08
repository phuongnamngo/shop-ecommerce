<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Support\CommerceException;
use App\Support\ErrorCode;

final class FakePaymentGateway implements PaymentGateway
{
    public function initiate(Order $order, PaymentTransaction $txn): array
    {
        if ($txn->provider === 'cod') {
            return ['redirect_url' => null];
        }

        return ['redirect_url' => 'https://payments.test/pay/'.$txn->idempotency_key];
    }

    public function verify(array $payload): VnPayVerificationResult
    {
        $responseCode = (string) ($payload['vnp_ResponseCode'] ?? '');
        $orderRef = (string) ($payload['vnp_TxnRef'] ?? '');
        $secureHash = (string) ($payload['vnp_SecureHash'] ?? '');
        $expected = $this->hash($payload);

        if ($secureHash === '' || ! hash_equals($expected, $secureHash)) {
            throw new CommerceException(ErrorCode::PAYMENT_INVALID_SIGNATURE, 'Invalid payment signature.', status: 400);
        }

        return new VnPayVerificationResult(
            ok: $responseCode === '00',
            responseCode: $responseCode,
            orderRef: $orderRef,
            providerTxnId: isset($payload['vnp_TransactionNo']) ? (string) $payload['vnp_TransactionNo'] : null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function hash(array $payload): string
    {
        $data = $payload;
        unset($data['vnp_SecureHash'], $data['vnp_SecureHashType']);
        ksort($data);
        $query = [];
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $query[] = $key.'='.$value;
            }
        }

        return hash_hmac('sha512', implode('&', $query), (string) config('commerce.vnpay.hash_secret'));
    }
}
