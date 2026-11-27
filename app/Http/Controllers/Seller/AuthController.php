<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerMember;
use App\Models\Store;
use App\Support\BuyerInput;
use App\Support\SellerInput;
use App\Support\SellerPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return SellerPage::render('SellerLogin');
    }

    public function showRegister()
    {
        return SellerPage::render('SellerRegister');
    }

    public function register(Request $request)
    {
        $request->merge([
            'business_registration_number' => SellerInput::prepareBusinessNumber($request->input('business_registration_number')),
        ]);

        $data = $request->validate([
            'seller_name' => ['required', 'string', 'min:2', 'max:100'],
            'business_registration_number' => SellerInput::businessNumberRules(),
            ...SellerInput::addressRules(),
            'business_license_test' => ['required', 'string', 'max:100'],
            'representative_name' => ['required', 'string', 'min:2', 'max:50'],
            'contact_phone' => ['required', 'string', 'regex:/^\d+$/', 'min:8', 'max:20'],
            'login_email' => ['required', 'email:rfc', 'max:100', Rule::unique('seller_members', 'login_email')],
            'password' => BuyerInput::password(),
            'settlement_bank_name' => ['required', 'string', 'max:50'],
            'settlement_account_number' => ['required', 'string', 'regex:/^\d+$/', 'min:8', 'max:30'],
            'settlement_account_holder' => ['required', 'string', 'max:50'],
        ], [
            'seller_name.required' => '판매자 이름을 입력해 주세요.',
            ...SellerInput::businessNumberMessages(),
            ...SellerInput::addressMessages(),
            'business_license_test.required' => '테스트용 사업자등록증 문구를 입력해 주세요.',
            'seller_name.min' => '판매자 이름은 2자 이상 입력해 주세요.',
            'representative_name.required' => '대표자명을 입력해 주세요.',
            'representative_name.min' => '대표자명은 2자 이상 입력해 주세요.',
            'contact_phone.required' => '연락처를 입력해 주세요.',
            'contact_phone.regex' => '연락처는 숫자만 입력해 주세요.',
            'contact_phone.min' => '연락처는 8자리 이상 숫자로 입력해 주세요.',
            'contact_phone.max' => '연락처는 20자리 이하로 입력해 주세요.',
            'login_email.required' => '이메일을 입력해 주세요.',
            'login_email.email' => '이메일에는 @와 도메인이 필요합니다.',
            'login_email.unique' => '이미 사용 중인 이메일입니다.',
            'settlement_bank_name.required' => '정산 은행을 입력해 주세요.',
            'settlement_account_number.required' => '정산 계좌번호를 입력해 주세요.',
            'settlement_account_number.regex' => '계좌번호는 숫자만 입력해 주세요.',
            'settlement_account_number.min' => '계좌번호는 8자리 이상 숫자로 입력해 주세요.',
            'settlement_account_number.max' => '계좌번호는 30자리 이하로 입력해 주세요.',
            'settlement_account_holder.required' => '예금주를 입력해 주세요.',
        ] + BuyerInput::messages());

        $member = DB::transaction(function () use ($data) {
            $seller = Seller::query()->create([
                'seller_name' => $data['seller_name'],
                'manager_name' => $data['representative_name'],
                'contact_phone' => $data['contact_phone'],
                'business_registration_number' => $data['business_registration_number'],
                'business_postal_code' => $data['business_postal_code'],
                'business_address_line1' => $data['business_address_line1'],
                'business_address_line2' => $data['business_address_line2'] ?: null,
                'business_address' => SellerInput::addressText($data),
                'business_license_file_url' => '테스트:'.$data['business_license_test'],
                'representative_name' => $data['representative_name'],
                'settlement_bank_name' => $data['settlement_bank_name'],
                'settlement_account_number_enc' => Crypt::encryptString($data['settlement_account_number']),
                'settlement_account_holder' => $data['settlement_account_holder'],
                'status' => 'APPROVED',
                'reviewed_at' => now(),
            ]);

            $member = SellerMember::query()->create([
                'seller_id' => $seller->id,
                'login_email' => $data['login_email'],
                'password_hash' => $data['password'],
                'member_name' => $data['representative_name'],
                'member_role' => 'OWNER',
                'status' => 'ACTIVE',
            ]);

            Store::query()->create([
                'seller_id' => $seller->id,
                'store_name' => $data['seller_name'].' 스토어',
                'introduction' => null,
                'operation_status' => 'OPEN',
            ]);

            return $member;
        });

        Auth::guard('seller')->login($member);
        $request->session()->regenerate();

        return response()->json(['redirect' => route('seller.home')]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login_email' => ['required', 'email', 'max:100'],
            'password' => ['required', 'string'],
        ], [
            'login_email.required' => '이메일을 입력해 주세요.',
            'login_email.email' => '이메일에는 @와 도메인이 필요합니다.',
            'password.required' => '비밀번호를 입력해 주세요.',
        ]);

        $member = SellerMember::query()
            ->with('seller')
            ->where('login_email', $data['login_email'])
            ->first();

        if (! $member || ! Hash::check($data['password'], $member->password_hash)) {
            return response()->json([
                'message' => '이메일 또는 비밀번호가 올바르지 않습니다.',
            ], 422);
        }

        $memberActive = $member->status === 'ACTIVE';
        $sellerActive = $member->seller?->status === 'APPROVED';

        if (! $memberActive || ! $sellerActive) {
            return response()->json([
                'message' => '사용할 수 없는 판매자 계정입니다.',
            ], 422);
        }

        Auth::guard('seller')->login($member);
        $request->session()->regenerate();
        $member->forceFill(['last_login_at' => now()])->save();

        return response()->json(['redirect' => route('seller.home')]);
    }

    public function logout(Request $request)
    {
        Auth::guard('seller')->logout();
        $request->session()->regenerateToken();

        return response()->json(['redirect' => route('seller.login')]);
    }
}
