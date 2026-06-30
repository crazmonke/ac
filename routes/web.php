<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/apartments', 'placeholder', [
    'title' => '아파트 검색',
    'description' => '단지 검색과 선택을 위한 페이지입니다.',
]);

Route::view('/login', 'placeholder', [
    'title' => '로그인',
    'description' => '인증 기능 연결 전 임시 로그인 안내 페이지입니다.',
])->name('login');

Route::view('/community', 'placeholder', [
    'title' => '입주민 커뮤니티',
    'description' => '인증된 입주민 전용 커뮤니티 메인 페이지입니다.',
]);

Route::view('/community/free', 'placeholder', [
    'title' => '자유게시판',
    'description' => '자유롭게 소통하는 기본 게시판입니다.',
]);

Route::view('/community/info', 'placeholder', [
    'title' => '생활정보 게시판',
    'description' => '생활 팁과 동네 정보를 공유합니다.',
]);

Route::view('/community/market', 'placeholder', [
    'title' => '나눔장터',
    'description' => '나눔 및 중고 거래를 위한 게시판입니다.',
]);

Route::view('/community/lost', 'placeholder', [
    'title' => '분실물 게시판',
    'description' => '분실물 등록 및 확인 페이지입니다.',
]);

Route::view('/community/complaints', 'placeholder', [
    'title' => '민원/건의',
    'description' => '단지 민원과 건의 사항을 등록합니다.',
]);

Route::view('/community/owners', 'placeholder', [
    'title' => '소유자 게시판',
    'description' => '소유자 인증 사용자 전용 공간입니다.',
]);

Route::view('/community/tenants', 'placeholder', [
    'title' => '임차인 게시판',
    'description' => '임차인 인증 사용자 전용 공간입니다.',
]);

Route::prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index']);
    Route::get('/boards', [AdminDashboardController::class, 'boards']);
    Route::get('/reports', [AdminDashboardController::class, 'reports']);
});
