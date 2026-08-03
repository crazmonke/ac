<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\PostTemplate;
use Illuminate\Http\Request;

class PostTemplateController extends Controller
{
    public function index()
    {
        $templates = PostTemplate::ordered()->paginate(15);
        $boardNames = $this->boardNamesBySlug();

        return view('admin.post-templates.index', compact('templates', 'boardNames'));
    }

    public function create()
    {
        return view('admin.post-templates.create', [
            'boardOptions' => $this->boardOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateTemplate($request);

        PostTemplate::create($data);

        return redirect()->route('admin.post-templates.index')->with('success', '게시글 템플릿이 등록되었습니다.');
    }

    public function edit(PostTemplate $postTemplate)
    {
        return view('admin.post-templates.edit', [
            'template' => $postTemplate,
            'boardOptions' => $this->boardOptions(),
        ]);
    }

    public function update(Request $request, PostTemplate $postTemplate)
    {
        $data = $this->validateTemplate($request, $postTemplate);

        $postTemplate->update($data);

        return redirect()->route('admin.post-templates.index')->with('success', '게시글 템플릿이 수정되었습니다.');
    }

    public function destroy(PostTemplate $postTemplate)
    {
        $postTemplate->delete();

        return redirect()->route('admin.post-templates.index')->with('success', '게시글 템플릿이 삭제되었습니다.');
    }

    /**
     * 템플릿 폼 공통 검증 + 질문 정규화.
     * 질문 key는 한 번 부여되면 불변(제목 placeholder·기존 게시글 답변이 참조)이며,
     * 신규 질문에는 기존 key와 겹치지 않는 다음 q{n}을 부여한다.
     */
    private function validateTemplate(Request $request, ?PostTemplate $existing = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'title_template' => 'required|string|max:160',
            'board_slugs' => 'nullable|array',
            'board_slugs.*' => 'string|exists:boards,slug',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
            'questions' => 'required|array|min:1|max:' . PostTemplate::MAX_QUESTIONS,
            'questions.*.key' => 'nullable|string|max:10',
            'questions.*.label' => 'required|string|max:255',
            'questions.*.type' => 'required|in:' . implode(',', PostTemplate::QUESTION_TYPES),
            'questions.*.required' => 'nullable|boolean',
            'questions.*.output_format' => 'nullable|string|max:500',
            'questions.*.max_length' => 'nullable|integer|min:1|max:1000',
            'questions.*.options' => 'nullable|array|max:20',
            'questions.*.options.*.label' => 'nullable|string|max:100',
            'questions.*.options.*.sentence' => 'nullable|string|max:500',
        ], [], [
            'name' => '템플릿 이름',
            'title_template' => '제목 생성 규칙',
            'questions' => '질문 목록',
        ]);

        $usedKeys = collect($existing?->questions ?? [])
            ->pluck('key')->filter()->values()->all();

        $questions = [];
        foreach (array_values($validated['questions']) as $index => $question) {
            $type = $question['type'];

            $options = [];
            if ($type === 'single' || $type === 'multiple') {
                foreach ($question['options'] ?? [] as $option) {
                    $label = trim((string) ($option['label'] ?? ''));
                    if ($label === '') {
                        continue;
                    }
                    $options[] = [
                        'label' => $label,
                        'sentence' => trim((string) ($option['sentence'] ?? '')),
                    ];
                }
                if (count($options) < 2) {
                    return $this->failQuestion($index, '선택형 질문에는 선택지를 2개 이상 입력해 주세요.');
                }
                $labels = array_column($options, 'label');
                if (count($labels) !== count(array_unique($labels))) {
                    return $this->failQuestion($index, '선택지 이름이 중복되었습니다.');
                }
            } elseif ($type === 'yes_no') {
                $options = [
                    ['label' => '예', 'sentence' => trim((string) ($question['options'][0]['sentence'] ?? ''))],
                    ['label' => '아니오', 'sentence' => trim((string) ($question['options'][1]['sentence'] ?? ''))],
                ];
            }

            $key = trim((string) ($question['key'] ?? ''));
            if ($key === '' || ! preg_match('/^q\d{1,2}$/', $key)) {
                $key = $this->nextKey($usedKeys);
            }
            $usedKeys[] = $key;

            $questions[] = [
                'key' => $key,
                'label' => trim($question['label']),
                'type' => $type,
                'required' => (bool) ($question['required'] ?? false),
                'output_format' => trim((string) ($question['output_format'] ?? '')),
                'options' => $options,
                'max_length' => $type === 'text' ? (int) ($question['max_length'] ?? 0) ?: null : null,
            ];
        }

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'title_template' => $validated['title_template'],
            'board_slugs' => ! empty($validated['board_slugs']) ? array_values(array_unique($validated['board_slugs'])) : null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'questions' => $questions,
        ];
    }

    private function failQuestion(int $index, string $message): never
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            "questions.{$index}" => [$message],
        ]);
    }

    private function nextKey(array $usedKeys): string
    {
        for ($n = 1; $n <= PostTemplate::MAX_QUESTIONS * 2; $n++) {
            if (! in_array("q{$n}", $usedKeys, true)) {
                return "q{$n}";
            }
        }

        return 'q' . (count($usedKeys) + 1);
    }

    /**
     * 게시판은 단지별로 복제되므로 slug 단위로 묶어 옵션을 만든다.
     */
    private function boardOptions(): array
    {
        return Board::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['slug', 'name'])
            ->unique('slug')
            ->map(fn ($board) => ['slug' => $board->slug, 'name' => $board->name])
            ->values()
            ->all();
    }

    private function boardNamesBySlug(): array
    {
        return Board::query()
            ->get(['slug', 'name'])
            ->unique('slug')
            ->pluck('name', 'slug')
            ->all();
    }
}
