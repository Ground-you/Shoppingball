<?php

namespace App\Payments;

class ManualPaymentGateway implements PaymentGateway
{
    public function driver(): string
    {
        return 'manual';
    }

    public function charge(array $payment): PaymentResult
    {
        return new PaymentResult(true, 'manual-'.$payment['orderNumber']);
    }

    public function refund(array $refund): PaymentResult
    {
        return new PaymentResult(true, 'manual-refund-'.$refund['orderNumber']);
    }
}
