<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminBoardController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:board_categories,id'],
            'apartment_id' => ['nullable', 'exists:apartments,id'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:80', Rule::unique('boards', 'slug')->where('apartment_id', $request->input('apartment_id'))],
            'description' => ['nullable', 'string'],
            'board_type' => ['required', Rule::in(config('community.board_types', []))],
            'read_role' => ['required', Rule::in(array_keys(config('community.roles', [])))],
            'write_role' => ['required', Rule::in(array_keys(config('community.roles', [])))],
            'comment_role' => ['required', Rule::in(array_merge(array_keys(config('community.roles', [])), ['none']))],
            'allow_file' => ['required', 'boolean'],
            'allow_anonymous' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;

        $board = Board::query()->create($data);

        return response()->json(['data' => $board], 201);
    }

    public function update(Request $request, int $id)
    {
        $board = Board::query()->findOrFail($id);

        $data = $request->validate([
            'category_id' => ['sometimes', 'exists:board_categories,id'],
            'apartment_id' => ['nullable', 'exists:apartments,id'],
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => [
                'sometimes',
                'string',
                'max:80',
                Rule::unique('boards', 'slug')
                    ->where('apartment_id', $request->input('apartment_id', $board->apartment_id))
                    ->ignore($board->id),
            ],
            'description' => ['nullable', 'string'],
            'board_type' => ['sometimes', Rule::in(config('community.board_types', []))],
            'read_role' => ['sometimes', Rule::in(array_keys(config('community.roles', [])))],
            'write_role' => ['sometimes', Rule::in(array_keys(config('community.roles', [])))],
            'comment_role' => ['sometimes', Rule::in(array_merge(array_keys(config('community.roles', [])), ['none']))],
            'allow_file' => ['sometimes', 'boolean'],
            'allow_anonymous' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $board->fill($data)->save();

        return response()->json(['data' => $board]);
    }

    public function destroy(int $id)
    {
        $board = Board::query()->findOrFail($id);
        $board->delete();

        return response()->noContent();
    }
}
