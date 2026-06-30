<?php

namespace App\Services;

use App\Models\Board;
use App\Models\User;
use App\Models\UserRole;

class PermissionService
{
    public function hasBoardPermission(?User $user, Board $board, string $permission): bool
    {
        $requiredRole = match ($permission) {
            'read' => $board->read_role,
            'write' => $board->write_role,
            'comment' => $board->comment_role,
            default => null,
        };

        if ($requiredRole === null || ! $board->is_active) {
            return false;
        }

        if ($requiredRole === 'none') {
            return false;
        }

        if ($requiredRole === 'guest') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($requiredRole === 'member') {
            return true;
        }

        $requiredLevel = $this->roleLevel($requiredRole);
        $currentLevel = $this->currentLevelForApartment($user->id, (int) $board->apartment_id);

        return $currentLevel >= $requiredLevel;
    }

    public function hasApartmentRole(User $user, int $apartmentId, string $minimumRole): bool
    {
        return $this->currentLevelForApartment($user->id, $apartmentId) >= $this->roleLevel($minimumRole);
    }

    public function hasAdminRole(User $user, int $apartmentId): bool
    {
        return UserRole::query()
            ->where('user_id', $user->id)
            ->where('apartment_id', $apartmentId)
            ->where('role', 'admin')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    private function currentLevelForApartment(int $userId, int $apartmentId): int
    {
        $roleRows = UserRole::query()
            ->where('user_id', $userId)
            ->where('apartment_id', $apartmentId)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->pluck('role');

        $level = $this->roleLevel('member');

        foreach ($roleRows as $role) {
            $level = max($level, $this->roleLevel($role));
        }

        return $level;
    }

    private function roleLevel(string $role): int
    {
        return (int) (config('community.roles')[$role] ?? PHP_INT_MAX);
    }
}
