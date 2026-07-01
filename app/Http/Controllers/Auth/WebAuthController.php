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
        return view('auth.register', [
            'redirect' => request()->query('redirect', '/'),
            'initialApartmentName' => Apartment::query()->find((int) request()->query('apartment_id', 0))?->name,
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'apartment_query' => ['required', 'string', 'max:120'],
            'apartment_id' => ['required', 'integer', 'exists:apartments,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'preferred_apartment_id' => $data['apartment_id'] ?? null,
            'password' => $data['password'],
        ]);

        $selection = $this->apartmentSelectionService->applySelection(
            $user,
            (int) $data['apartment_id'],
            $data['apartment_query'],
            'register',
            isset($data['latitude']) ? (float) $data['latitude'] : null,
            isset($data['longitude']) ? (float) $data['longitude'] : null
        );

        Auth::login($user);
        $request->session()->regenerate();

        $redirect = $this->safeRedirect($request->input('redirect'));

        if ($selection['selected_apartment'] && ($selection['auto_verified'] ?? false)) {
            $message = '회원가입이 완료되었습니다. 위치 기반 검증으로 입주민 인증이 우선 승인되었습니다.';
        } elseif ($selection['selected_apartment']) {
            $message = '회원가입이 완료되었습니다. 선택한 아파트 기준으로 입주민 인증을 진행해 주세요.';
        } else {
            $message = '회원가입이 완료되었습니다. 아파트 매칭 검수 요청이 접수되었습니다. 관리자 확인 후 인증을 진행할 수 있습니다.';
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
}
