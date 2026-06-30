<?php

namespace App\Http\Middleware;

use App\Models\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * @param  string  $requiredRole  Minimum role required to access route.
     * @param  string|null  $apartmentRouteKey  Route parameter holding apartment ID.
     */
    public function handle(Request $request, Closure $next, string $requiredRole, ?string $apartmentRouteKey = null): Response
    {
        if ($requiredRole === 'guest') {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        if ($requiredRole === 'member') {
            return $next($request);
        }

        $roles = config('community.roles', []);
        $requiredLevel = $roles[$requiredRole] ?? PHP_INT_MAX;

        $query = UserRole::query()->where('user_id', $user->id)
            ->where(function ($builder) {
                $builder->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        $apartmentId = $apartmentRouteKey ? (int) $request->route($apartmentRouteKey) : null;

        if ($apartmentId) {
            $query->where('apartment_id', $apartmentId);
        }

        $roleList = $query->pluck('role');

        $currentLevel = $roles['member'] ?? 1;

        foreach ($roleList as $role) {
            $currentLevel = max($currentLevel, $roles[$role] ?? 0);
        }

        if ($currentLevel < $requiredLevel) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
