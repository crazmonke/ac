<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Board;
use App\Models\BoardCategory;
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

        Board::query()->updateOrCreate(
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
    }
}
