<?php

namespace App\Payments;

interface PaymentGateway
{
    public function driver(): string;

    /**
     * @param  array{orderNumber:string,amount:int,orderName:string,paymentKey:?string}  $payment
     */
    public function charge(array $payment): PaymentResult;

    /**
     * @param  array{transactionId:string,orderNumber:string,amount:int,reason:string}  $refund
     */
    public function refund(array $refund): PaymentResult;
}
