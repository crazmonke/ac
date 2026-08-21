<?php

namespace App\Http\Controllers;

use App\Models\BlockedUser;
use Illuminate\Http\Request;

class BlockedUserController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $blockedUsers = BlockedUser::query()
            ->where('blocker_id', $user->id)
            ->with('blocked')
            ->latest()
            ->paginate(30);

        return view('user.blocked-users', [
            'user' => $user,
            'blockedUsers' => $blockedUsers,
        ]);
    }
}
