<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Support\BuyerInput;
use App\Support\CatalogStatus;
use App\Support\SellerContext;
use App\Support\SellerPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function edit()
    {
        $member = SellerContext::member();
        $seller = SellerContext::seller();

        return SellerPage::render('SellerProfile', [
            'section' => 'profile',
            'profile' => [
                'sellerName' => $seller->seller_name,
                'managerName' => $seller->manager_name ?? '',
                'representativeName' => $seller->representative_name ?? '',
                'email' => $member->login_email,
                'phone' => $seller->contact_phone ?? '',
                'businessNumber' => $seller->business_registration_number,
                'address' => $seller->business_address ?? '',
                'bankName' => $seller->settlement_bank_name ?? '',
                'accountNumber' => $this->decryptAccount($seller->settlement_account_number_enc),
                'accountHolder' => $seller->settlement_account_holder ?? '',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $member = SellerContext::member();
        $seller = SellerContext::seller();

        $data = $request->validate([
            'seller_name' => ['required', 'string', 'min:2', 'max:100'],
            'manager_name' => ['required', 'string', 'min:2', 'max:50'],
            'representative_name' => ['nullable', 'string', 'max:50'],
            'login_email' => ['required', 'email:rfc', 'max:100', Rule::unique('seller_members', 'login_email')->ignore($member->id)],
            'contact_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-]{8,20}$/'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'settlement_bank_name' => ['nullable', 'string', 'max:50'],
            'settlement_account_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\-]*$/'],
            'settlement_account_holder' => ['nullable', 'string', 'max:50'],
        ], [
            'seller_name.required' => '판매자 이름을 입력해 주세요.',
            'manager_name.required' => '담당자 이름을 입력해 주세요.',
            'login_email.required' => '이메일을 입력해 주세요.',
            'login_email.email' => '이메일에는 @와 도메인이 필요합니다.',
            'login_email.unique' => '이미 사용 중인 이메일입니다.',
            'contact_phone.regex' => '연락처는 숫자와 - 만 입력해 주세요.',
            'settlement_account_number.regex' => '계좌번호는 숫자와 - 만 입력해 주세요.',
        ]);

        $account = trim((string) ($data['settlement_account_number'] ?? ''));

        $seller->update([
            'seller_name' => $data['seller_name'],
            'manager_name' => $data['manager_name'],
            'representative_name' => $data['representative_name'] ?: null,
            'contact_phone' => $data['contact_phone'] ?: null,
            'business_address' => $data['business_address'] ?: null,
            'settlement_bank_name' => $data['settlement_bank_name'] ?: null,
            'settlement_account_number_enc' => $account !== '' ? Crypt::encryptString($account) : null,
            'settlement_account_holder' => $data['settlement_account_holder'] ?: null,
        ]);

        $member->update([
            'login_email' => $data['login_email'],
            'member_name' => $data['manager_name'],
        ]);

        return response()->json(['message' => '판매자 정보를 저장했습니다.']);
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => BuyerInput::password(),
        ], array_merge(BuyerInput::messages(), [
            'current_password.required' => '현재 비밀번호를 입력해 주세요.',
        ]));

        $member = SellerContext::member();

        if (! Hash::check($data['current_password'], $member->password_hash)) {
            return response()->json(['message' => '현재 비밀번호가 올바르지 않습니다.'], 422);
        }

        $member->update(['password_hash' => $data['password']]);

        return response()->json(['message' => '비밀번호를 변경했습니다.']);
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => '비밀번호를 입력해 주세요.',
        ]);

        $member = SellerContext::member();
        $seller = SellerContext::seller();

        if (! Hash::check($data['password'], $member->password_hash)) {
            return response()->json(['message' => '비밀번호가 올바르지 않습니다.'], 422);
        }

        $seller->forceFill([
            'status_id' => CatalogStatus::id('SELLER', 'WITHDRAWN', '탈퇴', 2),
        ])->save();
        $seller->delete();

        $member->forceFill([
            'status_id' => CatalogStatus::id('SELLER_MEMBER', 'WITHDRAWN', '탈퇴', 2),
        ])->save();
        $member->delete();

        Auth::guard('seller')->logout();
        $request->session()->regenerateToken();

        return response()->json(['redirect' => route('seller.login')]);
    }

    private function decryptAccount(?string $value): string
    {
        if (! $value) {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return '';
        }
    }
}
