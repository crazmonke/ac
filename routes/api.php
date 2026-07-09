<?php

use App\Http\Controllers\Api\AdminBoardController;
use App\Http\Controllers\Api\AdminReportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\PostFileController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:20,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:30,1');

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/fcm-token', [FcmTokenController::class, 'store']);
    Route::delete('/fcm-token', [FcmTokenController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/apartments/{apartmentId}/boards', [BoardController::class, 'index'])
        ->middleware('role:resident,apartmentId');

    Route::get('/boards/{boardId}/posts', [PostController::class, 'index'])
        ->middleware('board.access:read');
    Route::post('/boards/{boardId}/posts', [PostController::class, 'store'])
        ->middleware(['board.access:write', 'throttle:60,1']);

    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);

    Route::get('/posts/{postId}/comments', [CommentController::class, 'index']);
    Route::post('/posts/{postId}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:120,1');
    Route::put('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    Route::post('/posts/{id}/files', [PostFileController::class, 'store'])
        ->middleware('throttle:30,1');
    Route::get('/files/{id}', [PostFileController::class, 'show']);
    Route::delete('/files/{id}', [PostFileController::class, 'destroy']);

    Route::post('/reports', [ReportController::class, 'store'])
        ->middleware('role:resident');

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::post('/boards', [AdminBoardController::class, 'store']);
        Route::put('/boards/{id}', [AdminBoardController::class, 'update']);
        Route::delete('/boards/{id}', [AdminBoardController::class, 'destroy']);

        Route::get('/reports', [AdminReportController::class, 'index']);
        Route::put('/reports/{id}', [AdminReportController::class, 'update']);
    });
});
