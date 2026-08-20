<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPointController;
use App\Http\Controllers\Api\ApartmentSearchController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Auth\AccountSettingsController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ReportWebController;
use App\Http\Controllers\Community\CommunityBoardController;
use App\Http\Controllers\Community\CommunityPageController;
use App\Http\Controllers\MessageWebController;
use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\UserPointController;
use Illuminate\Support\Facades\Route;

Route::get('/uploads/{path}', function (string $path) {
    if ($path === '' || str_contains($path, '..')) {
        abort(404);
    }

    $relativePath = ltrim($path, '/');
    $candidates = [
        base_path('uploads/'.$relativePath),
        public_path('uploads/'.$relativePath),
    ];

    foreach ($candidates as $candidate) {
        if (! is_file($candidate)) {
            continue;
        }

        return response()->file($candidate, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    abort(404);
})->where('path', '.*');

Route::get('/', [PublicSiteController::class, 'home']);
Route::get('/boards/{slug}', [PublicSiteController::class, 'board']);
Route::get('/posts/{id}', [PublicSiteController::class, 'post']);

Route::get('/debug-boards-x9z2', function () {
    return response()->json(['alive' => true, 'php' => PHP_VERSION]);
});
Route::get('/debug-boards-view', function () {
    $step = 0;
    try {
        $step = 1;
        $memBefore = memory_get_usage(true);
        $memLimit  = ini_get('memory_limit');

        $step = 2;
        $viewPath   = resource_path('views/admin/boards.blade.php');
        $cachePath  = app('view')->getFinder()->find('admin.boards');
        $storageDir = storage_path('framework/views');
        $storagePerm = is_writable($storageDir);

        $step = 3;
        $boards = \App\Models\Board::query()->with('category')->orderBy('id', 'desc')->limit(5)->get();
        $cats   = \App\Models\BoardCategory::query()->orderBy('name')->get();
        $apts   = \App\Models\Apartment::query()->orderBy('name')->get();
        $roles  = config('community.board_permission_roles', []);
        $types  = config('community.board_types', []);

        $step = 4;
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);

        $step = 5;
        $html = view('admin.boards', [
            'boards'     => $boards,
            'categories' => $cats,
            'apartments' => $apts,
            'roleLabels' => $roles,
            'boardTypes' => $types,
        ])->render();

        $step = 6;
        return response()->json([
            'render'       => 'ok',
            'length'       => strlen($html),
            'memory_limit' => $memLimit,
            'memory_used'  => round(memory_get_peak_usage(true) / 1024 / 1024, 1) . 'MB',
            'storage_writable' => $storagePerm,
            'php'          => PHP_VERSION,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'render' => 'error',
            'step'   => $step,
            'msg'    => $e->getMessage(),
            'file'   => basename($e->getFile()) . ':' . $e->getLine(),
            'trace'  => collect(explode("\n", $e->getTraceAsString()))->take(10)->implode(' | '),
            'php'    => PHP_VERSION,
        ]);
    }
});
Route::view('/service/signup-guide', 'placeholder', [
    'title' => '회원가입 안내',
    'description' => '로그인 후 단지 인증을 완료하면 주민 전용 글을 열람할 수 있습니다.',
]);
Route::get('/terms', [PublicSiteController::class, 'terms']);
Route::get('/privacy', [PublicSiteController::class, 'privacy']);
Route::view('/support', 'support', [
    'title' => '문의 및 신고 안내',
]);

Route::view('/apartments', 'placeholder', [
    'title' => '공동주택 검색',
    'description' => '단지 검색과 선택을 위한 페이지입니다.',
]);
Route::get('/apartments/search', [ApartmentSearchController::class, 'index']);
Route::get('/apartments/regions', [ApartmentSearchController::class, 'regions']);
Route::get('/apartments/by-region', [ApartmentSearchController::class, 'byRegion']);
Route::get('/auth/check-email', [WebAuthController::class, 'checkEmail']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register'])->name('register.attempt');

    Route::get('/find-email', [WebAuthController::class, 'showFindEmail'])->name('find-email');
    Route::post('/find-email', [WebAuthController::class, 'findEmail'])->name('find-email.attempt');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('forgot-password.send');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPassword'])->name('reset-password');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('reset-password.update');
});

Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/community', [CommunityPageController::class, 'index']);
Route::get('/community/files/{id}', [CommunityBoardController::class, 'downloadFile']);
Route::get('/community/posts/{id}', [CommunityBoardController::class, 'showPost']);
Route::get('/community/api/apartments/{apartmentId}/boards', [BoardController::class, 'index'])
    ->middleware(['auth', 'role:resident,apartmentId']);

Route::middleware('auth')->group(function () {
    Route::get('/settings', [AccountSettingsController::class, 'show']);
    Route::put('/settings/profile', [AccountSettingsController::class, 'updateProfile']);
    Route::get('/settings/nickname-availability', [AccountSettingsController::class, 'checkNicknameAvailability']);
    Route::put('/settings/nickname', [AccountSettingsController::class, 'changeNickname']);
    Route::put('/settings/password', [AccountSettingsController::class, 'updatePassword']);
    Route::post('/settings/resident-verification-request', [AccountSettingsController::class, 'requestResidentVerification']);
    Route::post('/settings/withdraw-request', [AccountSettingsController::class, 'requestWithdrawal']);

    Route::get('/community/posts/{postId}/comments/{commentId}', [CommunityBoardController::class, 'showCommentDetail']);
    Route::post('/community/editor/photos', [CommunityBoardController::class, 'uploadEditorPhoto']);
    Route::post('/community/editor/videos', [CommunityBoardController::class, 'uploadEditorVideo']);
    Route::get('/community/compose', [CommunityBoardController::class, 'compose']);
    Route::get('/community/posts/{id}/edit', [CommunityBoardController::class, 'editPost']);
    Route::get('/community/boards/{slug}/create', [CommunityBoardController::class, 'createPost']);
    Route::get('/community/boards/{slug}/post-templates', [CommunityBoardController::class, 'postTemplates']);
    Route::post('/community/post-templates/{id}/preview', [CommunityBoardController::class, 'previewPostTemplate'])->whereNumber('id');
    Route::post('/community/boards/{slug}/posts', [CommunityBoardController::class, 'storePost']);
    Route::put('/community/posts/{id}', [CommunityBoardController::class, 'updatePost']);
    Route::delete('/community/posts/{id}', [CommunityBoardController::class, 'destroyPost']);
    Route::post('/community/posts/{id}/poll-votes', [CommunityBoardController::class, 'storePollVote']);
    Route::post('/community/posts/{id}/comments', [CommunityBoardController::class, 'storeComment']);
    Route::post('/community/posts/{id}/likes', [CommunityBoardController::class, 'likePost']);
    Route::delete('/community/posts/{id}/likes', [CommunityBoardController::class, 'unlikePost']);
    Route::post('/community/posts/{id}/hide', [CommunityBoardController::class, 'hidePost']);
    Route::post('/community/comments/{id}/likes', [CommunityBoardController::class, 'likeComment']);
    Route::delete('/community/comments/{id}/likes', [CommunityBoardController::class, 'unlikeComment']);
    Route::get('/community/comments/{id}/edit', [CommunityBoardController::class, 'editComment']);
    Route::put('/community/comments/{id}', [CommunityBoardController::class, 'updateComment']);
    Route::delete('/community/comments/{id}', [CommunityBoardController::class, 'destroyComment']);
    Route::delete('/community/files/{id}', [CommunityBoardController::class, 'destroyFile']);
    Route::get('/community/{slug}', [CommunityBoardController::class, 'board']);

    Route::get('/reports/new', [ReportWebController::class, 'create']);
    Route::post('/reports', [ReportWebController::class, 'store']);

    Route::get('/points', [UserPointController::class, 'index']);
    Route::get('/notifications', [NotificationController::class, 'index']);

    Route::get('/messages', [MessageWebController::class, 'inbox']);
    Route::get('/messages/compose', [MessageWebController::class, 'compose']);
    Route::get('/messages/users/search', [MessageWebController::class, 'searchUsers']);
    Route::post('/users/{id}/block', [CommunityBoardController::class, 'blockUser'])->whereNumber('id');
    Route::post('/messages', [MessageWebController::class, 'store'])->middleware('throttle:60,1');
    Route::get('/messages/{peerId}', [MessageWebController::class, 'conversation'])->whereNumber('peerId');
    Route::delete('/messages/conversations/{peerId}', [MessageWebController::class, 'deleteConversation'])->whereNumber('peerId');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index']);
    Route::get('/review-queue', [AdminDashboardController::class, 'reviewQueue']);
    Route::put('/review-queue/matches/{id}', [AdminDashboardController::class, 'updateMatchReview']);
    Route::post('/review-queue/matches/bulk', [AdminDashboardController::class, 'bulkUpdateMatchReviews']);
    Route::put('/review-queue/verifications/{id}', [AdminDashboardController::class, 'updateVerificationRequest']);
    Route::post('/review-queue/verifications/bulk', [AdminDashboardController::class, 'bulkUpdateVerificationRequests']);
    Route::put('/review-queue/residence-verifications/{id}', [AdminDashboardController::class, 'updateResidenceVerification']);
    Route::post('/review-queue/residence-verifications/{id}/retry', [AdminDashboardController::class, 'retryResidenceVerification']);
    Route::post('/review-queue/residence-verifications/bulk-auto-approve', [AdminDashboardController::class, 'bulkAutoApproveResidenceVerifications']);
    Route::put('/review-queue/merges/{id}', [AdminDashboardController::class, 'updateMergeCandidate']);
    Route::post('/review-queue/merges/bulk', [AdminDashboardController::class, 'bulkUpdateMergeCandidates']);
    Route::get('/boards', [AdminDashboardController::class, 'boards']);
    Route::get('/users', [AdminDashboardController::class, 'users']);
    Route::put('/users/{id}/verification', [AdminDashboardController::class, 'updateUserVerification']);
    Route::put('/users/{id}/access', [AdminDashboardController::class, 'updateUserAccess']);
    Route::put('/users/{id}/profile-lock', [AdminDashboardController::class, 'updateUserProfileLock']);
    Route::delete('/users/{id}', [AdminDashboardController::class, 'withdrawUser']);
    Route::post('/boards', [AdminDashboardController::class, 'storeBoard']);
    Route::put('/boards/{id}', [AdminDashboardController::class, 'updateBoard']);
    Route::delete('/boards/{id}', [AdminDashboardController::class, 'destroyBoard']);
    Route::get('/reports', [AdminDashboardController::class, 'reports']);
    Route::put('/reports/{id}', [AdminDashboardController::class, 'updateReport']);
    Route::get('/notifications', [AdminDashboardController::class, 'notifications']);
    Route::post('/notifications', [AdminDashboardController::class, 'sendNotification']);
    Route::get('/posts', [AdminDashboardController::class, 'posts']);
    Route::post('/posts/bulk', [AdminDashboardController::class, 'bulkPostAction']);
    Route::delete('/posts/{id}', [AdminDashboardController::class, 'destroyPost']);
    Route::post('/banners/upload-temp', [\App\Http\Controllers\Admin\BannerController::class, 'uploadTemp']);
    Route::resource('banners', \App\Http\Controllers\Admin\BannerController::class);
    Route::post('/banners/reorder', \App\Http\Controllers\Admin\BannerController::class . '@reorder');
    Route::resource('post-templates', \App\Http\Controllers\Admin\PostTemplateController::class)->except(['show']);
    Route::get('/fcm-diagnostic', [AdminDashboardController::class, 'fcmDiagnostic']);
    Route::get('/server-info', function () {
        return view('admin.server-info', [
            'phpSettings' => [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size'       => ini_get('post_max_size'),
                'max_execution_time'  => ini_get('max_execution_time'),
                'max_input_time'      => ini_get('max_input_time'),
                'memory_limit'        => ini_get('memory_limit'),
            ],
            'ffmpegPath'      => trim((string) shell_exec('which ffmpeg 2>/dev/null')),
            'ffmpegVersion'   => trim((string) shell_exec('ffmpeg -version 2>&1 | head -1')) ?: '확인 불가',
        ]);
    });

    Route::get('/points', [AdminPointController::class, 'index']);
    Route::get('/points/policy', [AdminPointController::class, 'policy']);
    Route::put('/points/policy', [AdminPointController::class, 'updatePolicy']);
    Route::get('/points/{userId}', [AdminPointController::class, 'userDetail']);
    Route::post('/points/{userId}/grant', [AdminPointController::class, 'grant']);
    Route::post('/points/{userId}/deduct', [AdminPointController::class, 'deduct']);
});
