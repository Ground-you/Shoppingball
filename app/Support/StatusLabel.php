<?php

namespace App\Support;

class StatusLabel
{
    public static function name(string $group, ?string $code): string
    {
        $labels = [
            'order' => [
                'PENDING_PAYMENT' => '결제대기',
                'PAID' => '결제완료',
                'PREPARING' => '상품준비중',
                'SHIPPING' => '상품발송',
                'DELIVERED' => '상품도착',
                'CONFIRMED' => '구매확정',
                'CANCELED' => '취소',
            ],
            'item' => [
                'PREPARING' => '상품준비중',
                'SHIPPING' => '상품발송',
                'DELIVERED' => '상품도착',
                'CONFIRMED' => '구매확정',
                'CANCELED' => '취소',
                'RETURNED' => '반품',
            ],
            'payment' => [
                'PENDING' => '결제대기',
                'PAID' => '결제완료',
                'FAILED' => '결제실패',
                'CANCELED' => '결제취소',
            ],
            'refund' => [
                'REQUESTED' => '환불요청',
                'PROCESSING' => '환불처리중',
                'COMPLETED' => '환불완료',
                'FAILED' => '환불실패',
            ],
            'claim' => [
                'REQUESTED' => '요청',
                'APPROVED' => '승인',
                'REJECTED' => '거절',
                'PICKING_UP' => '수거중',
                'COMPLETED' => '완료',
            ],
            'shipment' => [
                'PREPARING' => '상품준비중',
                'SHIPPING' => '상품발송',
                'DELIVERED' => '상품도착',
            ],
            'sale' => [
                'ON_SALE' => '판매중',
                'SOLD_OUT' => '품절',
                'STOPPED' => '판매중지',
            ],
        ];

        return $labels[$group][$code] ?? (string) $code;
    }
}
