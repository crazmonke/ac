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
        $data = $request->validate([
            'request_note' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if (! $user->preferred_apartment_id) {
            return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
                ->withErrors(['apartment_query' => '먼저 본인 아파트를 정확히 선택해 주세요.']);
        }

        $hasResidentRole = UserRole::query()
            ->where('user_id', $user->id)
            ->where('apartment_id', $user->preferred_apartment_id)
            ->where('role', 'resident')
            ->exists();

        if ($hasResidentRole) {
            return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
                ->with('status', '이미 입주민 인증이 승인되어 있습니다.');
        }

        $systemReviewNote = null;

        if (
            isset($data['latitude'], $data['longitude'])
            && $user->preferredApartment
        ) {
            $auto = $this->apartmentSelectionService->tryAutoApproveResidentByLocation(
                $user,
                $user->preferredApartment,
                (float) $data['latitude'],
                (float) $data['longitude'],
                'settings_verification'
            );

            if (($auto['approved'] ?? false) === true) {
                return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
                    ->with('status', '위치 기반 검증으로 입주민 인증이 우선 승인되었습니다.');
            }

            $systemReviewNote = $this->buildLocationMismatchNote($user->preferredApartment, $auto);
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
                'request_note' => trim((string) ($data['request_note'] ?? '')) ?: null,
                'admin_note' => $systemReviewNote ?? null,
            ]);
        } elseif (! empty($systemReviewNote)) {
            $existingPending->admin_note = $systemReviewNote;
            $existingPending->save();
        }

        return redirect('/settings?apartment_id=' . ($apartmentId > 0 ? $apartmentId : 1))
            ->with('status', '입주민 인증 요청이 접수되었습니다. 현재 선택된 아파트 기준으로 관리자 검수가 진행됩니다.');
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
            . '반면 신청 아파트(「' . $apartment->name . '」)의 행정구역은 「' . $apartmentLocation . '」로 확인되어 상호 일치하지 않아 자동 승인이 보류되었습니다. '
            . '관리자 검수 시 실거주 증빙(관리비 고지서, 임대차/등기 등) 확인 후 승인 여부를 결정해 주세요.';

        if (isset($details['distance_meters']) && is_numeric($details['distance_meters'])) {
            $message .= ' 참고 거리: 약 ' . number_format((int) $details['distance_meters']) . 'm';
        }

        return $message;
    }
}
