<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerMember;
use App\Support\SellerPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return SellerPage::render('SellerLogin');
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
            ->with(['status', 'seller.status'])
            ->where('login_email', $data['login_email'])
            ->first();

        if (! $member || ! Hash::check($data['password'], $member->password_hash)) {
            return response()->json([
                'message' => '이메일 또는 비밀번호가 올바르지 않습니다.',
            ], 422);
        }

        $memberActive = $member->status?->status_code === 'ACTIVE';
        $sellerActive = $member->seller?->status?->status_code === 'ACTIVE';

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
