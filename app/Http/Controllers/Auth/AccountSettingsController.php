<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountSettingsController extends Controller
{
    public function show(Request $request)
    {
        $apartmentId = (int) $request->query('apartment_id', 1);

        return view('auth.settings', [
            'apartmentId' => $apartmentId > 0 ? $apartmentId : 1,
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $apartmentId = (int) $request->input('apartment_id', 1);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->update($data);

        return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
            ->with('status', '프로필 정보가 업데이트되었습니다.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $apartmentId = (int) $request->input('apartment_id', 1);

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => $data['password'],
        ]);

        return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
            ->with('status', '비밀번호가 변경되었습니다.');
    }

    public function requestResidentVerification(Request $request)
    {
        $apartmentId = (int) $request->input('apartment_id', 1);

        return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
            ->with('status', '입주민 인증 요청이 접수되었습니다. 인증 프로세스 페이지 연동은 추후 추가됩니다.');
    }
}
