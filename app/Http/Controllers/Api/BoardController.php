<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardCategory;
use App\Models\Post;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function index(Request $request, int $apartmentId)
    {
        $categories = BoardCategory::query()
            ->where(function ($query) use ($apartmentId) {
                $query->where('apartment_id', $apartmentId)
                    ->orWhereNull('apartment_id');
            })
            ->orderBy('sort_order')
            ->get();

        $boardCollection = Board::query()
            ->where('is_active', true)
            ->where(function ($query) use ($apartmentId) {
                $query->where('apartment_id', $apartmentId)
                    ->orWhereNull('apartment_id');
            })
            ->whereNotIn('slug', ['terms', 'privacy'])
            ->where('name', 'not like', '%약관%')
            ->where('name', 'not like', '%개인정보%')
            ->orderBy('sort_order')
            ->get();

        $recentPostsByBoard = Post::query()
            ->whereIn('board_id', $boardCollection->pluck('id'))
            ->where('visibility', '!=', 'deleted')
            ->latest()
            ->get()
            ->groupBy('board_id')
            ->map(function ($posts) use ($apartmentId) {
                return $posts
                    ->take(5)
                    ->map(function (Post $post) use ($apartmentId) {
                        return [
                            'id' => (int) $post->id,
                            'title' => $post->title,
                            'view_count' => (int) $post->view_count,
                            'display_date' => $this->formatDisplayDate($post->created_at),
                            'url' => '/community/posts/' . $post->id . '?apartment_id=' . $apartmentId,
                        ];
                    })
                    ->values();
            });

        $boards = $boardCollection->groupBy('category_id');

        return response()->json([
            'data' => $categories->map(function (BoardCategory $category) use ($boards, $recentPostsByBoard) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'boards' => ($boards[$category->id] ?? collect())->map(function (Board $board) use ($recentPostsByBoard) {
                        return [
                            'id' => (int) $board->id,
                            'name' => $board->name,
                            'slug' => $board->slug,
                            'description' => $board->description,
                            'recent_posts' => $recentPostsByBoard->get($board->id, collect())->values(),
                        ];
                    })->values(),
                ];
            }),
        ]);
    }

    private function formatDisplayDate($createdAt): string
    {
        if (! $createdAt) {
            return '-';
        }

        $date = $createdAt->copy();

        if ($date->isSameDay(now())) {
            return $date->format('H:i');
        }

        return $date->format('m/d');
    }
}
