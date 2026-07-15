<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'apartment_id' => ['required', 'integer', 'exists:apartments,id'],
            'email'        => ['required', 'email', 'max:190'],
        ]);

        $user = User::query()
            ->where('name', $data['name'])
            ->where('email', $data['email'])
            ->where('preferred_apartment_id', (int) $data['apartment_id'])
            ->whereNull('withdrawn_at')
            ->where('access_allowed', true)
            ->first();

        if ($user) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->upsert(
                [['email' => $user->email, 'token' => Hash::make($token), 'created_at' => now()]],
                ['email']
            );

            Mail::to($user->email)->send(new PasswordResetMail($token, $user->email));
        }

        return back()->with(
            'status',
            '입력하신 정보와 일치하는 계정이 있을 경우, 비밀번호 변경 링크를 이메일로 발송했습니다. ' .
            '24시간 내에 이메일을 확인하여 변경해 주세요. ' .
            '(비밀번호는 암호화 저장되어 직접 확인이 불가능합니다.)'
        );
    }

    public function showResetPassword(Request $request, string $token)
    {
        $email = $request->query('email', '');

        $valid = $this->verifyToken($email, $token);

        return view('auth.reset-password', [
            'token'      => $token,
            'email'      => $email,
            'tokenValid' => $valid,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'token'     => ['required', 'string'],
            'email'     => ['required', 'email'],
            'password'  => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).+$/',
                'confirmed',
            ],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'password.min'   => '비밀번호는 최소 8자 이상이어야 합니다.',
            'password.regex' => '비밀번호는 영문자, 숫자, 특수문자를 각각 1개 이상 포함해야 합니다.',
        ]);

        if (! $this->verifyToken($data['email'], $data['token'])) {
            return back()->withErrors(['token' => '유효하지 않거나 만료된 비밀번호 변경 링크입니다. 다시 요청해 주세요.']);
        }

        $user = User::query()
            ->where('email', $data['email'])
            ->whereNull('withdrawn_at')
            ->first();

        if (! $user) {
            return back()->withErrors(['email' => '해당 이메일로 가입된 계정을 찾을 수 없습니다.']);
        }

        // 위치 검증
        if (! isset($data['latitude'], $data['longitude'])) {
            return back()->withErrors([
                'location' => '위치 정보를 확인할 수 없어 비밀번호 변경이 제한됩니다. 위치 권한을 허용한 후 다시 시도해 주세요.',
            ]);
        }

        $complex = $user->preferredResidenceComplex;
        if ($complex && $complex->latitude && $complex->longitude) {
            $distance = $this->haversineDistance(
                (float) $data['latitude'],
                (float) $data['longitude'],
                $complex->latitude,
                $complex->longitude
            );

            if ($distance > 3000) {
                return back()->withErrors([
                    'location' => '현재 위치가 가입 시 등록한 공동주택(' . $complex->displayName() . ')과 다른 위치로 확인되어 비밀번호 변경이 제한됩니다. 해당 공동주택 반경 3km 이내에서 변경해 주세요.',
                ]);
            }
        }

        $user->update(['password' => $data['password']]);

        DB::table('password_reset_tokens')->where('email', $data['email'])->delete();

        return redirect('/login')->with('status', '비밀번호가 성공적으로 변경되었습니다. 새 비밀번호로 로그인해 주세요.');
    }

    private function verifyToken(string $email, string $token): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record || ! Hash::check($token, $record->token)) {
            return false;
        }

        if (Carbon::parse($record->created_at)->addHours(24)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return false;
        }

        return true;
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r    = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
