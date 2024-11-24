<?php

return [
    'gateway_driver' => env('PAYMENT_GATEWAY', 'zarinpal'),
    'zarinpal' => [
        'merchant' => env('ZARINPAL_MERCHENT', 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
    ],
    'redirect_cart_path' => env('REDIRECT_CART_PATH', 'http://localhost:8080/newpanel/payment-receipt'),
];
