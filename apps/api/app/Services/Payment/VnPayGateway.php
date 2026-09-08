<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Support\CommerceException;
use App\Support\ErrorCode;

final class VnPayGateway implements PaymentGateway
{
    public function initiate(Order $order, PaymentTransaction $txn): array
    {
        if ($txn->provider === 'cod') {
            return ['redirect_url' => null];
        }

        $tmnCode = (string) config('commerce.vnpay.tmn_code');
        $hashSecret = (string) config('commerce.vnpay.hash_secret');
        $payUrl = rtrim((string) config('commerce.vnpay.url'), '?');
        $returnUrl = (string) config('commerce.vnpay.return_url');
        $amount = (int) $order->grand_total * 100;
        $txnRef = $order->number;
        $createDate = now()->format('YmdHis');
        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => (string) $amount,
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => $txnRef,
            'vnp_OrderInfo' => 'Thanh toan '.$txnRef,
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_IpAddr' => request()->ip() ?? '127.0.0.1',
            'vnp_CreateDate' => $createDate,
        ];
        ksort($params);
        $hashData = [];
        $query = [];
        foreach ($params as $key => $value) {
            $hashData[] = urlencode($key).'='.urlencode($value);
            $query[] = urlencode($key).'='.urlencode($value);
        }
        $secureHash = hash_hmac('sha512', implode('&', $hashData), $hashSecret);
        $redirect = $payUrl.'?'.implode('&', $query).'&vnp_SecureHash='.$secureHash;

        return ['redirect_url' => $redirect];
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

        $amountOk = true;
        if (isset($payload['vnp_Amount'], $payload['_expected_amount_vnd'])) {
            $amountOk = (int) $payload['vnp_Amount'] === ((int) $payload['_expected_amount_vnd'] * 100);
        }

        return new VnPayVerificationResult(
            ok: $responseCode === '00' && $amountOk,
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
        unset($data['vnp_SecureHash'], $data['vnp_SecureHashType'], $data['_expected_amount_vnd']);
        ksort($data);
        $hashData = [];
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $hashData[] = urlencode((string) $key).'='.urlencode((string) $value);
            }
        }

        return hash_hmac('sha512', implode('&', $hashData), (string) config('commerce.vnpay.hash_secret'));
    }
}
