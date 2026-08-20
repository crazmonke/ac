<?php

use App\Http\Controllers\Api\AdminBoardController;
use App\Http\Controllers\Api\AdminReportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\AdminInquiryController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PostFileController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PostLikeController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// WebView(쿠키 세션)에서 앱용 Bearer 토큰을 발급받는 엔드포인트
Route::middleware('auth:web')->get('/app-token', function (Request $request) {
    $user = $request->user('web');
    $user->tokens()->where('name', 'app-fcm')->delete();
    $token = $user->createToken('app-fcm', ['fcm']);
    return response()->json(['token' => $token->plainTextToken]);
});

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

    Route::get('/boards/{boardId}/post-templates', [\App\Http\Controllers\Api\PostTemplateController::class, 'index'])
        ->middleware('board.access:read');
    Route::post('/post-templates/{id}/preview', [\App\Http\Controllers\Api\PostTemplateController::class, 'preview'])
        ->middleware('throttle:60,1');

    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    Route::post('/posts/{id}/like', [PostLikeController::class, 'toggle'])
        ->middleware('throttle:60,1');
    Route::post('/posts/{id}/hide', [PostController::class, 'hide']);

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

    Route::post('/messages', [MessageController::class, 'store'])
        ->middleware('throttle:60,1');
    Route::get('/messages', [MessageController::class, 'index']);
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);
    Route::get('/messages/quota', [MessageController::class, 'quota']);
    Route::delete('/messages/conversations/{peerId}', [MessageController::class, 'destroyConversation'])
        ->whereNumber('peerId');
    Route::get('/messages/{conversationId}', [MessageController::class, 'show'])
        ->whereNumber('conversationId');
    Route::put('/messages/{messageId}/read', [MessageController::class, 'markAsRead'])
        ->whereNumber('messageId');

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::post('/boards', [AdminBoardController::class, 'store']);
        Route::put('/boards/{id}', [AdminBoardController::class, 'update']);
        Route::delete('/boards/{id}', [AdminBoardController::class, 'destroy']);

        Route::get('/reports', [AdminReportController::class, 'index']);
        Route::put('/reports/{id}', [AdminReportController::class, 'update']);

        Route::get('/inquiries', [AdminInquiryController::class, 'index']);
        Route::get('/inquiries/{memberId}', [AdminInquiryController::class, 'show'])
            ->whereNumber('memberId');
    });
});
