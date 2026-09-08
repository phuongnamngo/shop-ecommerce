<?php

return [
    'reservation_ttl_minutes' => (int) env('COMMERCE_RESERVATION_TTL_MINUTES', 30),
    'vnpay' => [
        'tmn_code' => env('VNPAY_TMN_CODE', ''),
        'hash_secret' => env('VNPAY_HASH_SECRET', 'testing-vnpay-secret'),
        'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'return_url' => env('VNPAY_RETURN_URL', env('APP_URL', 'http://localhost').'/api/v1/payments/vnpay/return'),
    ],
];
