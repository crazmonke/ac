<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAuthController extends Controller
{
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
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $redirect = $this->safeRedirect($request->input('redirect'));

        return redirect($redirect ?? '/')->with('status', '회원가입이 완료되었습니다. 단지 인증 후 주민 전용 글을 볼 수 있습니다.');
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
