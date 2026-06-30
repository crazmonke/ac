<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardCategory;
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

        $boards = Board::query()
            ->where('is_active', true)
            ->where(function ($query) use ($apartmentId) {
                $query->where('apartment_id', $apartmentId)
                    ->orWhereNull('apartment_id');
            })
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category_id');

        return response()->json([
            'data' => $categories->map(function (BoardCategory $category) use ($boards) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'boards' => ($boards[$category->id] ?? collect())->values(),
                ];
            }),
        ]);
    }
}
