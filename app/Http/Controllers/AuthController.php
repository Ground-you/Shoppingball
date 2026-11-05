<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\Status;
use App\Support\BuyerInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('app', [
            'page' => $this->page('Login'),
        ]);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login_id' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ], [
            'login_id.required' => '아이디를 입력해 주세요.',
            'password.required' => '비밀번호를 입력해 주세요.',
        ]);

        $buyer = Buyer::query()->where('login_id', $data['login_id'])->first();

        if (! $buyer || ! Hash::check($data['password'], $buyer->password_hash)) {
            return response()->json([
                'message' => '아이디 또는 비밀번호가 올바르지 않습니다.',
            ], 422);
        }

        Auth::guard('buyer')->login($buyer);
        $request->session()->regenerate();
        $buyer->forceFill(['last_login_at' => now()])->save();

        return response()->json(['redirect' => route('home')]);
    }

    public function showRegister()
    {
        return view('app', [
            'page' => $this->page('Register'),
        ]);
    }

    public function register(Request $request)
    {
        BuyerInput::prepare($request);

        $data = $request->validate([
            'login_id' => BuyerInput::loginId(),
            'password' => BuyerInput::password(),
            'buyer_name' => BuyerInput::name(),
            'nickname' => BuyerInput::nickname(),
            'email' => BuyerInput::email(),
            'phone' => BuyerInput::phone(),
            'marketing_consent' => ['boolean'],
        ], BuyerInput::messages());

        $buyer = Buyer::query()->create([
            'login_id' => $data['login_id'],
            'password_hash' => $data['password'],
            'buyer_name' => $data['buyer_name'],
            'nickname' => $data['nickname'] ?: null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'marketing_consent' => (bool) ($data['marketing_consent'] ?? false),
            'status_id' => $this->buyerStatus('ACTIVE', '활성', 1)->id,
        ]);

        Auth::guard('buyer')->login($buyer);
        $request->session()->regenerate();

        return response()->json(['redirect' => route('home')]);
    }

    public function logout(Request $request)
    {
        Auth::guard('buyer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['redirect' => route('home')]);
    }

    public function showWithdraw()
    {
        return view('app', [
            'page' => $this->page('Withdraw'),
        ]);
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => '비밀번호를 입력해 주세요.',
        ]);

        /** @var Buyer $buyer */
        $buyer = Auth::guard('buyer')->user();

        if (! Hash::check($data['password'], $buyer->password_hash)) {
            return response()->json([
                'message' => '비밀번호가 올바르지 않습니다.',
            ], 422);
        }

        $buyer->forceFill([
            'status_id' => $this->buyerStatus('WITHDRAWN', '탈퇴', 2)->id,
        ])->save();

        $hasHistory = DB::table('orders')->where('buyer_id', $buyer->id)->exists()
            || DB::table('reviews')->where('buyer_id', $buyer->id)->exists();

        if ($hasHistory) {
            $buyer->forceFill([
                'login_id' => 'withdrawn_'.$buyer->id,
                'email' => 'withdrawn_'.$buyer->id.'@deleted.local',
                'buyer_name' => '탈퇴회원',
                'nickname' => null,
                'phone' => null,
                'password_hash' => Str::password(32),
            ])->save();
            $buyer->delete();
        } else {
            $buyer->forceDelete();
        }

        Auth::guard('buyer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['redirect' => route('home')]);
    }

    private function page(string $component): array
    {
        $buyer = Auth::guard('buyer')->user();

        return [
            'component' => $component,
            'shortcuts' => ['오늘도착', '신선마켓', '골드딜', '기획전', '해외직구', '브랜드특가'],
            'buyer' => $buyer ? [
                'loginId' => $buyer->login_id,
                'name' => $buyer->buyer_name,
                'nickname' => $buyer->nickname,
            ] : null,
        ];
    }

    private function buyerStatus(string $code, string $name, int $order): Status
    {
        return Status::query()->firstOrCreate(
            ['status_group' => 'BUYER', 'status_code' => $code],
            ['status_name' => $name, 'display_order' => $order],
        );
    }
}
