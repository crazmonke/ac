<?php

namespace App\Http\Middleware;

use App\Models\Board;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BoardAccessMiddleware
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $boardId = $request->route('boardId') ?? $request->route('board');

        /** @var Board|null $board */
        $board = Board::query()->find($boardId);

        if (! $board || ! $board->is_active) {
            return response()->json(['message' => 'Board not found.'], 404);
        }

        if (! $this->permissionService->hasBoardPermission($request->user(), $board, $permission)) {
            if (! $request->user()) {
                return response()->json(['message' => 'Authentication required.'], 401);
            }

            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
