<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Board;
use App\Models\BoardCategory;
use App\Models\Post;
use App\Models\User;
use App\Models\UserRole;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $apartment = Apartment::query()->updateOrCreate(
            ['name' => '테스트 아파트'],
            [
                'road_address' => '서울시 강남구 테스트로 1',
                'jibun_address' => null,
                'sido' => '서울',
                'sigungu' => '강남구',
                'eupmyeondong' => '역삼동',
            ]
        );

        $category = BoardCategory::query()->updateOrCreate(
            [
                'apartment_id' => $apartment->id,
                'slug' => 'community',
            ],
            [
                'name' => '커뮤니티',
                'sort_order' => 0,
                'is_public' => false,
            ]
        );

        $publicCategory = BoardCategory::query()->updateOrCreate(
            [
                'apartment_id' => $apartment->id,
                'slug' => 'public-info',
            ],
            [
                'name' => '공개안내',
                'sort_order' => 1,
                'is_public' => true,
            ]
        );

        $freeBoard = Board::query()->updateOrCreate(
            [
                'apartment_id' => $apartment->id,
                'slug' => 'free',
            ],
            [
                'category_id' => $category->id,
                'name' => '자유게시판',
                'description' => '기본 커뮤니티 게시판',
                'board_type' => 'normal',
                'read_role' => 'resident',
                'write_role' => 'resident',
                'comment_role' => 'resident',
                'allow_file' => true,
                'allow_anonymous' => false,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        $noticeBoard = Board::query()->updateOrCreate(
            [
                'apartment_id' => $apartment->id,
                'slug' => 'notice',
            ],
            [
                'category_id' => $publicCategory->id,
                'name' => '공지사항',
                'description' => '비회원도 확인 가능한 서비스/단지 공지',
                'board_type' => 'notice',
                'read_role' => 'guest',
                'write_role' => 'admin',
                'comment_role' => 'none',
                'allow_file' => false,
                'allow_anonymous' => false,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $serviceNoticeBoard = Board::query()->updateOrCreate(
            [
                'apartment_id' => $apartment->id,
                'slug' => 'service-notice',
            ],
            [
                'category_id' => $publicCategory->id,
                'name' => '서비스 공지',
                'description' => '점검, 업데이트, 장애 안내',
                'board_type' => 'notice',
                'read_role' => 'guest',
                'write_role' => 'admin',
                'comment_role' => 'none',
                'allow_file' => false,
                'allow_anonymous' => false,
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        Board::query()->updateOrCreate(
            [
                'apartment_id' => $apartment->id,
                'slug' => 'policy',
            ],
            [
                'category_id' => $publicCategory->id,
                'name' => '개인정보/약관',
                'description' => '약관 및 개인정보처리 관련 문서',
                'board_type' => 'normal',
                'read_role' => 'guest',
                'write_role' => 'admin',
                'comment_role' => 'none',
                'allow_file' => false,
                'allow_anonymous' => false,
                'is_active' => true,
                'sort_order' => 3,
            ]
        );

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin1234!'),
            ]
        );

        $resident = User::query()->updateOrCreate(
            ['email' => 'resident@example.com'],
            [
                'name' => 'Resident User',
                'password' => Hash::make('resident1234!'),
            ]
        );

        UserRole::query()->updateOrCreate(
            [
                'user_id' => $admin->id,
                'apartment_id' => $apartment->id,
                'role' => 'admin',
            ],
            [
                'granted_at' => now(),
                'granted_by' => null,
            ]
        );

        UserRole::query()->updateOrCreate(
            [
                'user_id' => $admin->id,
                'apartment_id' => $apartment->id,
                'role' => 'resident',
            ],
            [
                'granted_at' => now(),
                'granted_by' => null,
            ]
        );

        UserRole::query()->updateOrCreate(
            [
                'user_id' => $resident->id,
                'apartment_id' => $apartment->id,
                'role' => 'resident',
            ],
            [
                'granted_at' => now(),
                'granted_by' => $admin->id,
            ]
        );

        Post::query()->updateOrCreate(
            [
                'board_id' => $noticeBoard->id,
                'title' => '커뮤니티 오픈 안내',
            ],
            [
                'apartment_id' => $apartment->id,
                'user_id' => $admin->id,
                'body' => '입주민 커뮤니티가 오픈되었습니다. 비회원은 공지와 일부 메뉴를 확인할 수 있습니다.',
                'is_notice' => true,
                'is_anonymous' => false,
                'visibility' => 'public',
                'view_count' => 0,
                'comment_count' => 0,
            ]
        );

        Post::query()->updateOrCreate(
            [
                'board_id' => $serviceNoticeBoard->id,
                'title' => '서비스 점검 공지',
            ],
            [
                'apartment_id' => $apartment->id,
                'user_id' => $admin->id,
                'body' => '매주 화요일 02:00~03:00에 서비스 점검이 진행됩니다.',
                'is_notice' => true,
                'is_anonymous' => false,
                'visibility' => 'public',
                'view_count' => 0,
                'comment_count' => 0,
            ]
        );

        Post::query()->updateOrCreate(
            [
                'board_id' => $freeBoard->id,
                'title' => '입주민 대표 토픽 샘플',
            ],
            [
                'apartment_id' => $apartment->id,
                'user_id' => $resident->id,
                'body' => '입주민 전용 토픽 베스트 영역에 노출되는 샘플 게시글입니다.',
                'is_notice' => false,
                'is_anonymous' => false,
                'visibility' => 'resident_only',
                'view_count' => 18,
                'comment_count' => 6,
            ]
        );
    }
}
