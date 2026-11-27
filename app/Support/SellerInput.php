<?php

namespace App\Support;

use Closure;
use Illuminate\Validation\Rule;

class SellerInput
{
    public static function prepareBusinessNumber(mixed $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        if (strlen($digits) !== 10) {
            return trim((string) $value);
        }

        return substr($digits, 0, 3).'-'.substr($digits, 3, 2).'-'.substr($digits, 5, 5);
    }

    public static function validBusinessNumber(string $value): bool
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        if (strlen($digits) !== 10) {
            return false;
        }

        $weights = [1, 3, 7, 1, 3, 7, 1, 3, 5];
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += (int) $digits[$index] * $weight;
        }

        $sum += intdiv((int) $digits[8] * 5, 10);
        $check = (10 - ($sum % 10)) % 10;

        return $check === (int) $digits[9];
    }

    public static function businessNumberRules(?int $ignoreSellerId = null): array
    {
        return [
            'required',
            'string',
            'max:20',
            function (string $attribute, mixed $value, Closure $fail) {
                if (! self::validBusinessNumber((string) $value)) {
                    $fail('사업자등록번호 형식이 올바르지 않습니다.');
                }
            },
            Rule::unique('sellers', 'business_registration_number')->ignore($ignoreSellerId),
        ];
    }

    public static function businessNumberMessages(): array
    {
        return [
            'business_registration_number.required' => '사업자등록번호를 입력해 주세요.',
            'business_registration_number.unique' => '이미 등록된 사업자등록번호입니다.',
        ];
    }

    public static function addressRules(): array
    {
        return [
            'business_postal_code' => ['required', 'string', 'regex:/^\d{5}$/'],
            'business_address_line1' => ['required', 'string', 'max:255'],
            'business_address_line2' => ['nullable', 'string', 'max:255'],
        ];
    }

    public static function addressMessages(): array
    {
        return [
            'business_postal_code.required' => '우편번호 찾기로 사업자 주소를 등록해 주세요.',
            'business_postal_code.regex' => '우편번호 찾기로 사업자 주소를 등록해 주세요.',
            'business_address_line1.required' => '사업자 주소를 입력해 주세요.',
        ];
    }

    public static function addressText(array $data): string
    {
        $detail = trim((string) ($data['business_address_line2'] ?? ''));

        return sprintf(
            '(%s) %s%s',
            $data['business_postal_code'],
            $data['business_address_line1'],
            $detail !== '' ? ' '.$detail : '',
        );
    }
}
