<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ResidentVerificationRequest;
use App\Models\UserRole;
use App\Models\UserResidence;
use App\Services\ApartmentSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AccountSettingsController extends Controller
{
    public function __construct(private readonly ApartmentSelectionService $apartmentSelectionService)
    {
    }

    public function show(Request $request)
    {
        $apartmentId = (int) $request->query('apartment_id', 1);
        $user = $request->user()->load([
            'preferredApartment',
            'preferredResidenceComplex',
            'preferredResidenceBuilding',
            'preferredResidenceUnit',
        ]);
        $latestVerificationRequest = ResidentVerificationRequest::query()
            ->with('apartment')
            ->where('user_id', $user->id)
            ->latest()
            ->first();
        $latestResidenceVerification = UserResidence::query()
            ->with(['complex', 'building', 'unit'])
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
        $hasResidentRole = $hasResidentRole || (bool) UserResidence::query()
            ->where('user_id', $user->id)
            ->where('verification_status', 'verified')
            ->exists();

        return view('auth.settings', [
            'apartmentId' => $apartmentId > 0 ? $apartmentId : 1,
            'user' => $user,
            'selectedApartment' => $user->preferredApartment,
            'latestVerificationRequest' => $latestVerificationRequest,
            'latestResidenceVerification' => $latestResidenceVerification,
            'latestMatchReview' => $latestMatchReview,
            'hasResidentRole' => $hasResidentRole,
            'isProfileLocked' => (bool) ($user->profile_locked ?? true),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $apartmentId = (int) $request->input('apartment_id', 1);

        if ((bool) ($user->profile_locked ?? true)) {
            return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
                ->withErrors(['name' => '현재 계정은 프로필 수정이 잠금 상태입니다. 관리자에게 해제를 요청해 주세요.']);
        }

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
            'residence_building_id' => ['required', 'integer', 'exists:residence_buildings,id'],
            'residence_dong' => ['nullable', 'string', 'max:40'],
            'residence_ho' => ['nullable', 'string', 'max:40'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $selection = $this->apartmentSelectionService->applySelection(
            $user,
            isset($data['apartment_id']) ? (int) $data['apartment_id'] : null,
            $data['apartment_query'],
            'settings',
            null,
            null,
            (int) $data['residence_building_id'],
            $data['residence_dong'] ?? null,
            $data['residence_ho'] ?? null
        );

        $message = $selection['selected_complex']
            ? '프로필 정보와 공동주택 설정이 업데이트되었습니다.'
            : '프로필 정보가 업데이트되었고 공동주택 매칭 검수 요청이 접수되었습니다.';

        return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
            ->with('status', $message);
    }

    public function requestWithdrawal(Request $request)
    {
        $user = $request->user();

        if ($user->withdrawn_at) {
            return redirect('/settings?apartment_id=' . max(1, (int) $request->input('apartment_id', 1)))
                ->with('status', '이미 탈퇴 처리된 계정입니다.');
        }

        $user->forceFill([
            'withdrawn_at' => now(),
            'access_allowed' => false,
        ])->save();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', '탈퇴 요청이 접수되어 계정이 비활성화되었습니다.');
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
        $user = $request->user()->load(['preferredApartment', 'preferredResidenceComplex', 'preferredResidenceBuilding']);
        $apartmentId = (int) $request->input('apartment_id', 1);
        $data = $request->validate([
            'request_note' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if (! $user->preferred_residence_complex_id || ! $user->preferred_residence_building_id) {
            return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
            ->withErrors(['apartment_query' => '먼저 본인 공동주택을 정확히 선택해 주세요.']);
        }

        $hasResidentRole = UserRole::query()
            ->where('user_id', $user->id)
            ->where('apartment_id', $user->preferred_apartment_id)
            ->where('role', 'resident')
            ->exists();

        if (! $hasResidentRole) {
            $hasResidentRole = UserResidence::query()
                ->where('user_id', $user->id)
                ->where('verification_status', 'verified')
                ->exists();
        }

        if ($hasResidentRole) {
            return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
                ->with('status', '이미 입주민 인증이 승인되어 있습니다.');
        }

        $systemReviewNote = null;

        if (isset($data['latitude'], $data['longitude']) && $user->preferredResidenceComplex && $user->preferredResidenceBuilding) {
            $auto = $this->apartmentSelectionService->tryAutoApproveResidentByResidence(
                $user,
                $user->preferredResidenceComplex,
                $user->preferredResidenceBuilding,
                (float) $data['latitude'],
                (float) $data['longitude'],
                'settings_verification',
                $user->preferredApartment
            );

            if (($auto['approved'] ?? false) === true) {
                return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
                    ->with('status', '위치 기반 검증으로 공동주택 인증이 우선 승인되었습니다.');
            }

            if ($user->preferredApartment) {
                $systemReviewNote = $this->buildLocationMismatchNote($user->preferredApartment, $auto);
            }
        }

        if (! $user->preferred_apartment_id) {
            UserResidence::query()
                ->where('user_id', $user->id)
                ->where('complex_id', $user->preferred_residence_complex_id)
                ->update([
                    'verification_status' => 'pending',
                    'verification_method' => 'manual',
                    'evidence_meta' => [
                        'request_note' => trim((string) ($data['request_note'] ?? '')) ?: null,
                        'system_review_note' => $systemReviewNote,
                        'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
                        'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
                    ],
                ]);

            return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
                ->with('status', '공동주택 인증 요청이 접수되었습니다. 관리자 검수가 진행됩니다.');
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
                'residence_complex_id' => $user->preferred_residence_complex_id,
                'residence_building_id' => $user->preferred_residence_building_id,
                'residence_unit_id' => $user->preferred_residence_unit_id,
                'verification_method' => 'manual',
                'status' => 'pending',
                'request_note' => trim((string) ($data['request_note'] ?? '')) ?: null,
                'admin_note' => $systemReviewNote ?? null,
            ]);
        } elseif (! empty($systemReviewNote)) {
            $existingPending->admin_note = $systemReviewNote;
            $existingPending->save();
        }

        return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
            ->with('status', '입주민 인증 요청이 접수되었습니다. 현재 선택된 공동주택 기준으로 관리자 검수가 진행됩니다.');
    }

    private function buildLocationMismatchNote(Apartment $apartment, array $autoResult): ?string
    {
        $details = (array) ($autoResult['details'] ?? []);

        $gpsSido = trim((string) ($details['gps_sido'] ?? ''));
        $gpsSigungu = trim((string) ($details['gps_sigungu'] ?? ''));
        $gpsDong = trim((string) ($details['gps_dong'] ?? ''));

        $currentLocation = trim(implode(' ', array_filter([$gpsSido, $gpsSigungu, $gpsDong])));
        $apartmentLocation = trim(implode(' ', array_filter([
            $apartment->sido,
            $apartment->sigungu,
            $apartment->eupmyeondong,
        ])));

        if ($currentLocation === '') {
            return '시스템 위치 검증 결과, 신청 당시 GPS 위치를 행정구역 기준으로 확정할 수 없어 자동 승인이 보류되었습니다. 위치 권한 허용 상태와 단말 위치 서비스 설정을 확인한 뒤 재요청을 권장합니다.';
        }

        $message = '시스템 위치 검증 결과, 신청 당시 GPS 기준 현재 위치는 「' . $currentLocation . '」로 확인되었습니다. '
            . '반면 신청 공동주택(「' . $apartment->name . '」)의 행정구역은 「' . $apartmentLocation . '」로 확인되어 상호 일치하지 않아 자동 승인이 보류되었습니다. '
            . '관리자 검수 시 실거주 증빙(관리비 고지서, 임대차/등기 등) 확인 후 승인 여부를 결정해 주세요.';

        if (isset($details['distance_meters']) && is_numeric($details['distance_meters'])) {
            $message .= ' 참고 거리: 약 ' . number_format((int) $details['distance_meters']) . 'm';
        }

        return $message;
    }
}
