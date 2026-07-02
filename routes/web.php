<?php

use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Api\ApartmentSearchController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Auth\AccountSettingsController;
use App\Http\Controllers\ReportWebController;
use App\Http\Controllers\Community\CommunityBoardController;
use App\Http\Controllers\Community\CommunityPageController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicSiteController::class, 'home']);
Route::get('/boards/{slug}', [PublicSiteController::class, 'board']);
Route::get('/posts/{id}', [PublicSiteController::class, 'post']);
Route::view('/service/signup-guide', 'placeholder', [
    'title' => '회원가입 안내',
    'description' => '로그인/회원가입 후 단지 인증을 완료하면 주민 전용 글을 열람할 수 있습니다.',
]);
Route::get('/terms', [PublicSiteController::class, 'terms']);
Route::get('/privacy', [PublicSiteController::class, 'privacy']);

Route::view('/apartments', 'placeholder', [
    'title' => '아파트 검색',
    'description' => '단지 검색과 선택을 위한 페이지입니다.',
]);
Route::get('/apartments/search', [ApartmentSearchController::class, 'index']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [WebAuthController::class, 'register'])->name('register.attempt');
});

Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/community', [CommunityPageController::class, 'index']);
Route::get('/community/api/apartments/{apartmentId}/boards', [BoardController::class, 'index'])
    ->middleware(['auth', 'role:resident,apartmentId']);

Route::middleware('auth')->group(function () {
    Route::get('/settings', [AccountSettingsController::class, 'show']);
    Route::put('/settings/profile', [AccountSettingsController::class, 'updateProfile']);
    Route::put('/settings/password', [AccountSettingsController::class, 'updatePassword']);
    Route::post('/settings/resident-verification-request', [AccountSettingsController::class, 'requestResidentVerification']);
    Route::post('/settings/withdraw-request', [AccountSettingsController::class, 'requestWithdrawal']);

    Route::get('/community/posts/{id}', [CommunityBoardController::class, 'showPost']);
    Route::get('/community/compose', [CommunityBoardController::class, 'compose']);
    Route::get('/community/posts/{id}/edit', [CommunityBoardController::class, 'editPost']);
    Route::get('/community/boards/{slug}/create', [CommunityBoardController::class, 'createPost']);
    Route::post('/community/boards/{slug}/posts', [CommunityBoardController::class, 'storePost']);
    Route::put('/community/posts/{id}', [CommunityBoardController::class, 'updatePost']);
    Route::delete('/community/posts/{id}', [CommunityBoardController::class, 'destroyPost']);
    Route::post('/community/posts/{id}/poll-votes', [CommunityBoardController::class, 'storePollVote']);
    Route::post('/community/posts/{id}/comments', [CommunityBoardController::class, 'storeComment']);
    Route::get('/community/comments/{id}/edit', [CommunityBoardController::class, 'editComment']);
    Route::put('/community/comments/{id}', [CommunityBoardController::class, 'updateComment']);
    Route::delete('/community/comments/{id}', [CommunityBoardController::class, 'destroyComment']);
    Route::get('/community/files/{id}', [CommunityBoardController::class, 'downloadFile']);
    Route::delete('/community/files/{id}', [CommunityBoardController::class, 'destroyFile']);
    Route::get('/community/{slug}', [CommunityBoardController::class, 'board']);

    Route::get('/reports/new', [ReportWebController::class, 'create']);
    Route::post('/reports', [ReportWebController::class, 'store']);
});

Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index']);
    Route::get('/review-queue', [AdminDashboardController::class, 'reviewQueue']);
    Route::put('/review-queue/matches/{id}', [AdminDashboardController::class, 'updateMatchReview']);
    Route::put('/review-queue/verifications/{id}', [AdminDashboardController::class, 'updateVerificationRequest']);
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
});
