<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ResidentVerificationRequest;
use App\Models\UserRole;
use App\Services\ApartmentSelectionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountSettingsController extends Controller
{
    public function __construct(private readonly ApartmentSelectionService $apartmentSelectionService)
    {
    }

    public function show(Request $request)
    {
        $apartmentId = (int) $request->query('apartment_id', 1);
        $user = $request->user()->load('preferredApartment');
        $latestVerificationRequest = ResidentVerificationRequest::query()
            ->with('apartment')
            ->where('user_id', $user->id)
            ->latest()
            ->first();
        $latestMatchReview = $user->apartmentMatchReviews()->latest()->first();
        $hasResidentRole = $user->preferred_apartment_id
            ? UserRole::query()
                ->where('user_id', $user->id)
                ->where('apartment_id', $user->preferred_apartment_id)
                ->where('role', 'resident')
                ->exists()
            : false;

        return view('auth.settings', [
            'apartmentId' => $apartmentId > 0 ? $apartmentId : 1,
            'user' => $user,
            'selectedApartment' => $user->preferredApartment,
            'latestVerificationRequest' => $latestVerificationRequest,
            'latestMatchReview' => $latestMatchReview,
            'hasResidentRole' => $hasResidentRole,
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
            'apartment_query' => ['required', 'string', 'max:120'],
            'apartment_id' => ['nullable', 'integer', 'exists:apartments,id'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $selection = $this->apartmentSelectionService->applySelection(
            $user,
            isset($data['apartment_id']) ? (int) $data['apartment_id'] : null,
            $data['apartment_query'],
            'settings'
        );

        $message = $selection['selected_apartment']
            ? '프로필 정보와 아파트 설정이 업데이트되었습니다.'
            : '프로필 정보가 업데이트되었고 아파트 매칭 검수 요청이 접수되었습니다.';

        return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
            ->with('status', $message);
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
        $user = $request->user()->load('preferredApartment');
        $apartmentId = (int) $request->input('apartment_id', 1);

        if (! $user->preferred_apartment_id) {
            return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
                ->withErrors(['apartment_query' => '먼저 본인 아파트를 정확히 선택해 주세요.']);
        }

        $existingPending = ResidentVerificationRequest::query()
            ->where('user_id', $user->id)
            ->where('apartment_id', $user->preferred_apartment_id)
            ->where('status', 'pending')
            ->first();

        if (! $existingPending) {
            ResidentVerificationRequest::query()->create([
                'user_id' => $user->id,
                'apartment_id' => $user->preferred_apartment_id,
                'status' => 'pending',
                'request_note' => trim((string) $request->input('request_note', '')) ?: null,
            ]);
        }

        return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
            ->with('status', '입주민 인증 요청이 접수되었습니다. 현재 선택된 아파트 기준으로 관리자 검수가 진행됩니다.');
    }
}
