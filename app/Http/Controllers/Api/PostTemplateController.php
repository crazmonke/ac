<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\PostTemplate;
use App\Services\PostTemplateRenderer;
use Illuminate\Http\Request;

class PostTemplateController extends Controller
{
    public function __construct(
        private readonly PostTemplateRenderer $renderer,
    ) {
    }

    public function index(int $boardId)
    {
        $board = Board::query()->findOrFail($boardId);

        $templates = PostTemplate::availableForBoard((string) $board->slug)
            ->map(fn (PostTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'questions' => $template->questions ?? [],
            ]);

        return response()->json(['data' => $templates]);
    }

    public function preview(Request $request, int $id)
    {
        $template = PostTemplate::query()->active()->findOrFail($id);

        $payload = $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $answers = $this->renderer->validateAnswers($template, $payload['answers']);
        $rendered = $this->renderer->render($template, $answers);

        return response()->json(['data' => [
            'title' => $rendered['title'],
            'body_html' => $rendered['body_html'],
            'answers' => $answers,
        ]]);
    }
}
