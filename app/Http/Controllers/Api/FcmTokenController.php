<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:32'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        $token = FcmToken::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'device_id' => $data['device_id'] ?? null,
                'token' => $data['token'],
            ],
            [
                'platform' => $data['platform'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'app_version' => $data['app_version'] ?? null,
                'last_seen_at' => now(),
                'enabled' => true,
            ]
        );

        return response()->json([
            'data' => $token,
        ]);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'token' => ['nullable', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ]);

        $query = FcmToken::query()->where('user_id', $request->user()->id);

        if (! empty($data['token'])) {
            $query->where('token', $data['token']);
        }

        if (empty($data['token']) && ! empty($data['device_id'])) {
            $query->where('device_id', $data['device_id']);
        }

        $query->delete();

        return response()->noContent();
    }
}