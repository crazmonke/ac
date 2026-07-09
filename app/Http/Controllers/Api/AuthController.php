<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $token = $user->createToken('default')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        if (! (bool) ($user->access_allowed ?? true) || $user->withdrawn_at) {
            return response()->json(['message' => 'This account is not allowed to sign in.'], 403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('default')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    public function logout(Request $request)
    {
        $data = $request->validate([
            'fcm_token' => ['nullable', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($data['fcm_token']) || ! empty($data['device_id'])) {
            FcmToken::query()
                ->where('user_id', $request->user()->id)
                ->when(! empty($data['fcm_token']), function ($query) use ($data) {
                    $query->where('token', $data['fcm_token']);
                })
                ->when(empty($data['fcm_token']) && ! empty($data['device_id']), function ($query) use ($data) {
                    $query->where('device_id', $data['device_id']);
                })
                ->delete();
        }

        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
