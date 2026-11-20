<?php

namespace App\Payments;

use Illuminate\Support\Facades\Http;

class TossPaymentsGateway implements PaymentGateway
{
    public function __construct(
        private string $secretKey,
        private string $apiUrl,
    ) {}

    public function driver(): string
    {
        return 'toss';
    }

    public function charge(array $payment): PaymentResult
    {
        if ($this->secretKey === '') {
            return new PaymentResult(false, '', '토스페이먼츠 시크릿 키가 설정되지 않았습니다.');
        }

        if (empty($payment['paymentKey'])) {
            return new PaymentResult(false, '', '토스페이먼츠 결제 키가 없습니다. 결제 위젯 연동 후 다시 시도해 주세요.');
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->post(rtrim($this->apiUrl, '/').'/v1/payments/confirm', [
                'paymentKey' => $payment['paymentKey'],
                'orderId' => $payment['orderNumber'],
                'amount' => $payment['amount'],
            ]);

        if ($response->failed()) {
            return new PaymentResult(false, '', $response->json('message') ?: '토스페이먼츠 결제에 실패했습니다.');
        }

        return new PaymentResult(true, (string) $response->json('paymentKey'));
    }

    public function refund(array $refund): PaymentResult
    {
        if ($this->secretKey === '') {
            return new PaymentResult(false, '', '토스페이먼츠 시크릿 키가 설정되지 않았습니다.');
        }

        $response = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->post(rtrim($this->apiUrl, '/').'/v1/payments/'.$refund['transactionId'].'/cancel', [
                'cancelReason' => $refund['reason'],
                'cancelAmount' => $refund['amount'],
            ]);

        if ($response->failed()) {
            return new PaymentResult(false, '', $response->json('message') ?: '토스페이먼츠 환불에 실패했습니다.');
        }

        $cancelKey = $response->json('cancels.0.transactionKey') ?: $refund['transactionId'];

        return new PaymentResult(true, (string) $cancelKey);
    }
}
