<?php

return [

    /*
    | 기본값은 테스트 결제입니다. 토스페이먼츠로 바꿀 때는
    | PAYMENT_DRIVER=toss 와 TOSS_CLIENT_KEY, TOSS_SECRET_KEY 를 넣습니다.
    | 결제는 POST /v1/payments/confirm, 환불은 POST /v1/payments/{paymentKey}/cancel 입니다.
    */

    'driver' => env('PAYMENT_DRIVER', 'manual'),

    'toss' => [
        'client_key' => env('TOSS_CLIENT_KEY'),
        'secret_key' => env('TOSS_SECRET_KEY'),
        'api_url' => env('TOSS_API_URL', 'https://api.tosspayments.com'),
    ],

];
