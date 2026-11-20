<?php

namespace App\Payments;

class PaymentResult
{
    public function __construct(
        public bool $successful,
        public string $transactionId = '',
        public string $message = '',
    ) {}
}
