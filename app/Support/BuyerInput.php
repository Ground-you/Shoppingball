<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BuyerInput
{
    public static function prepare(Request $request): void
    {
        $request->merge([
            'nickname' => $request->filled('nickname') ? $request->input('nickname') : null,
            'phone' => $request->filled('phone') ? $request->input('phone') : null,
        ]);
    }

    public static function loginId(): array
    {
        return ['required', 'string', 'min:4', 'max:20', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique('buyers', 'login_id')];
    }

    public static function password(bool $confirmed = true): array
    {
        $rules = ['required', 'string', 'min:8', 'max:64', 'regex:/^(?=.*[A-Z])(?=.*[^A-Za-z0-9]).+$/'];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    public static function name(): array
    {
        return ['required', 'string', 'min:2', 'max:20'];
    }

    public static function nickname(): array
    {
        return ['nullable', 'string', 'min:2', 'max:10', 'regex:/^[가-힣a-zA-Z0-9]+$/u'];
    }

    public static function email(?int $ignoreBuyerId = null): array
    {
        $unique = Rule::unique('buyers', 'email');

        if ($ignoreBuyerId) {
            $unique = $unique->ignore($ignoreBuyerId);
        }

        return ['required', 'email:rfc', 'max:100', $unique];
    }

    public static function phone(): array
    {
        return ['nullable', 'regex:/^01[016789]-?\d{3,4}-?\d{4}$/'];
    }

    public static function messages(): array
    {
        return [
            'login_id.required' => '아이디를 입력해 주세요.',
            'login_id.min' => '아이디는 4자 이상이어야 합니다.',
            'login_id.max' => '아이디는 20자 이하여야 합니다.',
            'login_id.regex' => '아이디는 영문, 숫자, _ 만 사용할 수 있습니다.',
            'login_id.unique' => '이미 사용 중인 아이디입니다.',
            'password.required' => '비밀번호를 입력해 주세요.',
            'password.min' => '비밀번호는 8자 이상이어야 합니다.',
            'password.regex' => '비밀번호는 대문자 1개와 특수문자 1개 이상이 필요합니다.',
            'password.confirmed' => '비밀번호 확인이 일치하지 않습니다.',
            'buyer_name.required' => '이름을 입력해 주세요.',
            'buyer_name.min' => '이름은 2자 이상이어야 합니다.',
            'buyer_name.max' => '이름은 20자 이하여야 합니다.',
            'nickname.min' => '닉네임은 2자 이상이어야 합니다.',
            'nickname.max' => '닉네임은 10자 이하여야 합니다.',
            'nickname.regex' => '닉네임은 한글, 영문, 숫자만 사용할 수 있습니다.',
            'email.required' => '이메일을 입력해 주세요.',
            'email.email' => '이메일에는 @와 도메인이 필요합니다. 예: name@example.com',
            'email.unique' => '이미 사용 중인 이메일입니다.',
            'phone.regex' => '휴대폰 번호 형식을 확인해 주세요. 예: 010-1234-5678',
        ];
    }
}
