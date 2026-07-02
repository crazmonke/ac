<?php

namespace App\Services;

use App\Models\Board;
use App\Models\Post;
use App\Models\User;
use App\Models\UserRole;

class PermissionService
{
    private const VERIFIED_ROLES = ['resident', 'household_rep', 'owner_verified', 'tenant_verified', 'admin'];
    private const LEGACY_VERIFIED_BOARD_ROLES = ['resident', 'household_rep', 'owner_verified', 'tenant_verified'];

    public function hasBoardPermission(?User $user, Board $board, string $permission): bool
    {
        $requiredRole = match ($permission) {
            'read' => $board->read_role,
            'write' => $board->write_role,
            'comment' => $board->comment_role,
            default => null,
        };

        $requiredRole = $this->normalizeBoardPermissionRole($requiredRole);

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

        if ($requiredRole === 'verified') {
            return $this->hasVerifiedRole($user, $board->apartment_id ? (int) $board->apartment_id : null);
        }

        if ($requiredRole === 'admin' && $board->apartment_id === null) {
            return UserRole::query()
                ->where('user_id', $user->id)
                ->where('role', 'admin')
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();
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

    public function hasVerifiedRole(User $user, ?int $apartmentId = null): bool
    {
        $query = UserRole::query()
            ->where('user_id', $user->id)
            ->whereIn('role', self::VERIFIED_ROLES)
            ->where(function ($subQuery) {
                $subQuery->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($apartmentId !== null) {
            $query->where('apartment_id', $apartmentId);
        }

        return $query->exists();
    }

    public function canReadPostDetail(?User $user, Post $post): bool
    {
        if ($post->visibility === 'deleted') {
            return false;
        }

        $scope = (string) ($post->audience_scope ?? 'all');

        // 단지 게시글은 게시판 read_role보다 우선해서 동일 단지 인증회원만 허용.
        if ($scope === 'apartment') {
            if (! $user) {
                return false;
            }

            return $this->hasVerifiedRole($user, (int) $post->apartment_id);
        }

        $post->loadMissing('board');

        if (! $post->board || ! $this->hasBoardPermission($user, $post->board, 'read')) {
            return false;
        }

        return true;
    }

    public function resolvePostAccessLabel(?User $user, Post $post): ?string
    {
        if ($this->canReadPostDetail($user, $post)) {
            return null;
        }

        $scope = (string) ($post->audience_scope ?? 'all');

        if (! $user) {
            return '회원 전용';
        }

        if ($scope === 'apartment') {
            return '인증 회원 전용';
        }

        $post->loadMissing('board');
        $requiredReadRole = $this->normalizeBoardPermissionRole($post->board?->read_role);

        if ($requiredReadRole === 'guest') {
            return null;
        }

        if ($requiredReadRole === 'admin') {
            return '관리자 전용';
        }

        if (! $this->hasVerifiedRole($user)) {
            if ($requiredReadRole === 'member') {
                return null;
            }

            return '인증 회원 전용';
        }

        return '인증 회원 전용';
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

    private function normalizeBoardPermissionRole(?string $role): ?string
    {
        if ($role === null) {
            return null;
        }

        if (in_array($role, self::LEGACY_VERIFIED_BOARD_ROLES, true)) {
            return 'verified';
        }

        return $role;
    }
}
