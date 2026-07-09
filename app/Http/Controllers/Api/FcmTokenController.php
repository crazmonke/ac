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

        $userId = $request->user()->id;
        $deviceId = $data['device_id'] ?? null;

        $query = FcmToken::query()->where('user_id', $userId);

        if (! empty($deviceId)) {
            $token = $query->where('device_id', $deviceId)->first();

            if (! $token) {
                $token = new FcmToken();
                $token->user_id = $userId;
                $token->device_id = $deviceId;
            }
        } else {
            $token = $query->where('token', $data['token'])->first() ?? new FcmToken();
            $token->user_id = $userId;
        }

        $token->fill([
            'token' => $data['token'],
            'platform' => $data['platform'] ?? null,
            'device_name' => $data['device_name'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'last_seen_at' => now(),
            'enabled' => true,
        ]);

        if (! empty($deviceId)) {
            $token->device_id = $deviceId;
        }

        $token->save();

        if (! empty($deviceId)) {
            FcmToken::query()
                ->where('user_id', $userId)
                ->where('device_id', $deviceId)
                ->where('id', '!=', $token->id)
                ->delete();
        }

        FcmToken::query()
            ->where('user_id', '!=', $userId)
            ->where('token', $data['token'])
            ->delete();

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