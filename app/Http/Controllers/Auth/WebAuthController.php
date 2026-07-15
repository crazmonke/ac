<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ApartmentMatchReview;
use App\Models\User;
use App\Services\ApartmentSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAuthController extends Controller
{
    public function __construct(private readonly ApartmentSelectionService $apartmentSelectionService)
    {
    }

    public function showLogin()
    {
        return view('auth.login', [
            'redirect' => request()->query('redirect', '/'),
        ]);
    }

    public function checkEmail(Request $request)
    {
        $email = $request->query('email', '');
        if (! $email) {
            return response()->json(['available' => false, 'message' => '이메일을 입력해 주세요.']);
        }

        $exists = User::query()->where('email', $email)->whereNull('withdrawn_at')->exists();

        if ($exists) {
            return response()->json(['available' => false, 'message' => '이미 가입된 이메일입니다.']);
        }

        return response()->json(['available' => true, 'message' => '사용 가능한 이메일입니다.']);
    }

    public function showFindEmail()
    {
        return view('auth.find-email');
    }

    public function findEmail(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'apartment_id' => ['required', 'integer', 'exists:apartments,id'],
        ]);

        $users = User::query()
            ->where('name', $data['name'])
            ->where('preferred_apartment_id', (int) $data['apartment_id'])
            ->whereNull('withdrawn_at')
            ->get();

        $emails = $users->map(fn ($u) => $this->maskEmail($u->email))->values()->toArray();

        return back()->with([
            'find_result' => true,
            'emails' => $emails,
            'searched_name' => $data['name'],
        ]);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = min(2, strlen($local));
        $masked = substr($local, 0, $visible) . str_repeat('*', max(strlen($local) - $visible, 0));

        return $masked . '@' . $domain;
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => '이메일 또는 비밀번호가 올바르지 않습니다.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $this->canLoginUser($user)) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => '현재 계정은 로그인할 수 없습니다. 관리자에게 문의해 주세요.',
            ])->onlyInput('email');
        }

        $user?->forceFill(['last_login_at' => now()])->save();

        if ($this->isAdminUser($user)) {
            return redirect('/admin');
        }

        $redirect = $this->safeRedirect($request->input('redirect'));

        if ($redirect !== null) {
            return redirect($redirect);
        }

        return redirect()->intended('/');
    }

    public function showRegister()
    {
        $initialApartment = Apartment::query()->find((int) request()->query('apartment_id', 0));

        return view('auth.register', [
            'redirect' => request()->query('redirect', '/'),
            'initialApartmentName' => $initialApartment?->name,
            'initialResidenceBuildingId' => null,
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'apartment_query' => ['nullable', 'string', 'max:120'],
            'apartment_id' => ['required', 'integer', 'exists:apartments,id'],
            'residence_building_id' => ['nullable', 'integer', 'exists:residence_buildings,id'],
            'residence_dong' => ['nullable', 'string', 'max:40'],
            'residence_ho' => ['nullable', 'string', 'max:40'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).+$/', 'confirmed'],
        ], [
            'email.unique' => '이미 가입된 이메일입니다.',
            'password.min' => '비밀번호는 최소 8자 이상이어야 합니다.',
            'password.regex' => '비밀번호는 영문자, 숫자, 특수문자를 각각 1개 이상 포함해야 합니다.',
        ]);

        $apartmentQuery = $data['apartment_query']
            ?? Apartment::query()->find((int) $data['apartment_id'])?->name
            ?? '';

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'preferred_apartment_id' => (int) $data['apartment_id'],
            'password' => $data['password'],
        ]);

        $selection = $this->apartmentSelectionService->applySelection(
            $user,
            (int) $data['apartment_id'],
            $apartmentQuery,
            'register',
            isset($data['latitude']) ? (float) $data['latitude'] : null,
            isset($data['longitude']) ? (float) $data['longitude'] : null,
               isset($data['residence_building_id']) ? (int) $data['residence_building_id'] : null,
            $data['residence_dong'] ?? null,
            $data['residence_ho'] ?? null
        );

        Auth::login($user);
        $request->session()->regenerate();

        $redirect = $this->safeRedirect($request->input('redirect'));

        if ($selection['selected_complex'] && ($selection['auto_verified'] ?? false)) {
            $message = '회원가입이 완료되었습니다. 위치 기반 검증으로 공동주택 인증이 우선 승인되었습니다.';
        } elseif ($selection['selected_complex']) {
            $message = '회원가입이 완료되었습니다. 선택한 공동주택 기준으로 인증을 진행해 주세요.';
        } else {
            $message = '회원가입이 완료되었습니다. 공동주택 매칭 검수 요청이 접수되었습니다. 관리자 확인 후 인증을 진행할 수 있습니다.';
        }

        return redirect($redirect ?? '/')->with('status', $message);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function safeRedirect(?string $redirect): ?string
    {
        if (! $redirect) {
            return null;
        }

        if (str_starts_with($redirect, '/')) {
            return $redirect;
        }

        return null;
    }

    private function isAdminUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->userRoles()
            ->where('role', 'admin')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    private function canLoginUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (! (bool) ($user->access_allowed ?? true)) {
            return false;
        }

        if ($user->withdrawn_at) {
            return false;
        }

        return true;
    }
}
