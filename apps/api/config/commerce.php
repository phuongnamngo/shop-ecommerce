<?php

return [
    'reservation_ttl_minutes' => (int) env('COMMERCE_RESERVATION_TTL_MINUTES', 30),
    'default_weight_grams' => (int) env('COMMERCE_DEFAULT_WEIGHT_GRAMS', 500),
    'shipping_driver' => env('COMMERCE_SHIPPING_DRIVER', 'ghn'),
    'sms_driver' => env('COMMERCE_SMS_DRIVER', 'unavailable'),
    'ghn' => [
        'token' => env('GHN_TOKEN', ''),
        'shop_id' => env('GHN_SHOP_ID', ''),
        'from_province_id' => env('GHN_FROM_PROVINCE_ID', ''),
        'from_district_id' => env('GHN_FROM_DISTRICT_ID', ''),
        'from_ward_code' => env('GHN_FROM_WARD_CODE', ''),
        'from_phone' => env('GHN_FROM_PHONE', ''),
        'from_address' => env('GHN_FROM_ADDRESS', ''),
        'webhook_token' => env('GHN_WEBHOOK_TOKEN', ''),
        'base_url' => env('GHN_BASE_URL', 'https://dev-online-gateway.ghn.vn'),
        'default_lwh_cm' => array_map(
            intval(...),
            array_slice(array_pad(explode(',', (string) env('COMMERCE_GHN_DEFAULT_LWH_CM', '10,10,10')), 3, 10), 0, 3),
        ),
    ],
    'vnpay' => [
        'tmn_code' => env('VNPAY_TMN_CODE', ''),
        'hash_secret' => env('VNPAY_HASH_SECRET', 'testing-vnpay-secret'),
        'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
        'return_url' => env('VNPAY_RETURN_URL', env('APP_URL', 'http://localhost').'/api/v1/payments/vnpay/return'),
        'refund_url' => env('VNPAY_REFUND_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),
    ],
];
