<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('board_categories') || ! Schema::hasTable('boards')) {
            return;
        }

        $now = now();

        $categories = [
            [
                'slug' => 'community',
                'name' => '커뮤니티',
                'sort_order' => 0,
                'is_public' => false,
            ],
            [
                'slug' => 'public-info',
                'name' => '공개안내',
                'sort_order' => 1,
                'is_public' => true,
            ],
        ];

        $categoryIdsBySlug = [];

        foreach ($categories as $category) {
            $existingId = DB::table('board_categories')
                ->whereNull('apartment_id')
                ->where('slug', $category['slug'])
                ->value('id');

            if ($existingId) {
                DB::table('board_categories')
                    ->where('id', $existingId)
                    ->update([
                        'name' => $category['name'],
                        'sort_order' => $category['sort_order'],
                        'is_public' => $category['is_public'],
                        'updated_at' => $now,
                    ]);

                $categoryIdsBySlug[$category['slug']] = (int) $existingId;
                continue;
            }

            $newId = DB::table('board_categories')->insertGetId([
                'apartment_id' => null,
                'name' => $category['name'],
                'slug' => $category['slug'],
                'sort_order' => $category['sort_order'],
                'is_public' => $category['is_public'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $categoryIdsBySlug[$category['slug']] = (int) $newId;
        }

        $boards = [
            [
                'category_slug' => 'community',
                'name' => '커뮤니티',
                'slug' => 'free',
                'description' => '기본 커뮤니티 게시판',
                'board_type' => 'normal',
                'read_role' => 'guest',
                'write_role' => 'verified',
                'comment_role' => 'verified',
                'allow_file' => true,
                'allow_anonymous' => false,
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'category_slug' => 'public-info',
                'name' => '공지사항',
                'slug' => 'notice',
                'description' => '비회원도 확인 가능한 서비스/단지 공지',
                'board_type' => 'notice',
                'read_role' => 'guest',
                'write_role' => 'admin',
                'comment_role' => 'none',
                'allow_file' => false,
                'allow_anonymous' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'public-info',
                'name' => '서비스 공지',
                'slug' => 'service-notice',
                'description' => '점검, 업데이트, 장애 안내',
                'board_type' => 'notice',
                'read_role' => 'guest',
                'write_role' => 'admin',
                'comment_role' => 'none',
                'allow_file' => false,
                'allow_anonymous' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'category_slug' => 'public-info',
                'name' => '개인정보/약관',
                'slug' => 'policy',
                'description' => '약관 및 개인정보처리 관련 문서',
                'board_type' => 'normal',
                'read_role' => 'guest',
                'write_role' => 'admin',
                'comment_role' => 'none',
                'allow_file' => false,
                'allow_anonymous' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'category_slug' => 'community',
                'name' => '모두의 의견',
                'slug' => 'poll',
                'description' => '투표게시판',
                'board_type' => 'poll',
                'read_role' => 'verified',
                'write_role' => 'verified',
                'comment_role' => 'verified',
                'allow_file' => true,
                'allow_anonymous' => true,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($boards as $board) {
            $categoryId = $categoryIdsBySlug[$board['category_slug']] ?? null;

            if (! $categoryId) {
                continue;
            }

            $existingId = DB::table('boards')
                ->whereNull('apartment_id')
                ->where('slug', $board['slug'])
                ->value('id');

            $payload = [
                'category_id' => $categoryId,
                'name' => $board['name'],
                'description' => $board['description'],
                'board_type' => $board['board_type'],
                'read_role' => $board['read_role'],
                'write_role' => $board['write_role'],
                'comment_role' => $board['comment_role'],
                'allow_file' => $board['allow_file'],
                'allow_anonymous' => $board['allow_anonymous'],
                'is_active' => $board['is_active'],
                'sort_order' => $board['sort_order'],
                'updated_at' => $now,
            ];

            if ($existingId) {
                DB::table('boards')->where('id', $existingId)->update($payload);
                continue;
            }

            DB::table('boards')->insert(array_merge($payload, [
                'apartment_id' => null,
                'slug' => $board['slug'],
                'created_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        // This recovery migration is intentionally non-destructive.
    }
};
