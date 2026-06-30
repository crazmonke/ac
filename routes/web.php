<?php

use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Community\CommunityBoardController;
use App\Http\Controllers\Community\CommunityPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/apartments', 'placeholder', [
    'title' => '아파트 검색',
    'description' => '단지 검색과 선택을 위한 페이지입니다.',
]);

Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/community', [CommunityPageController::class, 'index'])->middleware('auth');
Route::get('/community/api/apartments/{apartmentId}/boards', [BoardController::class, 'index'])
    ->middleware(['auth', 'role:resident,apartmentId']);

Route::middleware('auth')->group(function () {
    Route::get('/community/posts/{id}', [CommunityBoardController::class, 'showPost']);
    Route::post('/community/boards/{slug}/posts', [CommunityBoardController::class, 'storePost']);
    Route::put('/community/posts/{id}', [CommunityBoardController::class, 'updatePost']);
    Route::delete('/community/posts/{id}', [CommunityBoardController::class, 'destroyPost']);
    Route::post('/community/posts/{id}/comments', [CommunityBoardController::class, 'storeComment']);
    Route::put('/community/comments/{id}', [CommunityBoardController::class, 'updateComment']);
    Route::delete('/community/comments/{id}', [CommunityBoardController::class, 'destroyComment']);
    Route::get('/community/files/{id}', [CommunityBoardController::class, 'downloadFile']);
    Route::delete('/community/files/{id}', [CommunityBoardController::class, 'destroyFile']);
    Route::get('/community/{slug}', [CommunityBoardController::class, 'board']);
});

Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index']);
    Route::get('/boards', [AdminDashboardController::class, 'boards']);
    Route::post('/boards', [AdminDashboardController::class, 'storeBoard']);
    Route::put('/boards/{id}', [AdminDashboardController::class, 'updateBoard']);
    Route::delete('/boards/{id}', [AdminDashboardController::class, 'destroyBoard']);
    Route::get('/reports', [AdminDashboardController::class, 'reports']);
    Route::put('/reports/{id}', [AdminDashboardController::class, 'updateReport']);
});
