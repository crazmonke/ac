<?php

namespace App\Services;

use App\Models\PostTemplate;
use Illuminate\Validation\ValidationException;

/**
 * 설문조사형 게시글 템플릿의 답변 검증과 게시글(제목/본문) 생성을 담당하는 단일 소스.
 * 웹 작성/수정, 앱 API, 미리보기가 모두 이 클래스를 통해 동일한 결과를 얻는다.
 */
class PostTemplateRenderer
{
    /**
     * 답변을 질문 정의에 따라 검증하고 정규화된 형태로 반환한다.
     *
     * 답변 형식: {"q1": "선택지 label", "q2": ["label", ...], "q3": "주관식", "q4": "예"}
     *
     * @return array<string, string|array<string>> 정규화된 답변 (질문에 없는 키 제거)
     *
     * @throws ValidationException
     */
    public function validateAnswers(PostTemplate $template, array $answers): array
    {
        $normalized = [];
        $errors = [];

        foreach ($template->questions ?? [] as $question) {
            $key = $question['key'] ?? null;
            if ($key === null) {
                continue;
            }

            $type = $question['type'] ?? 'text';
            $required = (bool) ($question['required'] ?? false);
            $raw = $answers[$key] ?? null;

            if ($this->isEmptyAnswer($raw)) {
                if ($required) {
                    $errors["answers.{$key}"] = ['"'.($question['label'] ?? $key).'" 질문에 답변해 주세요.'];
                }
                continue;
            }

            $optionLabels = array_values(array_map(
                fn ($option) => (string) ($option['label'] ?? ''),
                $question['options'] ?? []
            ));

            if ($type === 'multiple') {
                $values = is_array($raw) ? array_values(array_map('strval', $raw)) : [(string) $raw];
                $invalid = array_diff($values, $optionLabels);
                if ($invalid !== []) {
                    $errors["answers.{$key}"] = ['선택지에 없는 답변이 포함되어 있습니다.'];
                    continue;
                }
                $normalized[$key] = $values;
                continue;
            }

            if (is_array($raw)) {
                $errors["answers.{$key}"] = ['답변 형식이 올바르지 않습니다.'];
                continue;
            }
            $value = trim((string) $raw);

            if ($type === 'single' || $type === 'yes_no') {
                $allowed = $type === 'yes_no' && $optionLabels === [] ? ['예', '아니오'] : $optionLabels;
                if (! in_array($value, $allowed, true)) {
                    $errors["answers.{$key}"] = ['선택지에 없는 답변입니다.'];
                    continue;
                }
                $normalized[$key] = $value;
                continue;
            }

            // text
            $maxLength = (int) ($question['max_length'] ?? 0);
            if ($maxLength > 0 && mb_strlen($value) > $maxLength) {
                $errors["answers.{$key}"] = ["답변은 {$maxLength}자 이내로 입력해 주세요."];
                continue;
            }
            $normalized[$key] = $value;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $normalized;
    }

    /**
     * 정규화된 답변으로 게시글 제목/본문 HTML을 생성한다.
     *
     * - 본문: 질문 순서대로 output_format의 {answer}를 치환한 문장 + 선택된 옵션의 조건부 sentence를 <p>로 연결
     * - 제목: title_template의 {q1}~{q10}을 답변으로 치환
     *
     * @return array{title: string, body_html: string}
     */
    public function render(PostTemplate $template, array $answers): array
    {
        $paragraphs = [];
        $titleReplacements = [];

        foreach ($template->questions ?? [] as $question) {
            $key = $question['key'] ?? null;
            if ($key === null || ! array_key_exists($key, $answers)) {
                continue;
            }

            $answer = $answers[$key];
            $answerText = is_array($answer) ? implode(', ', $answer) : (string) $answer;
            $titleReplacements['{'.$key.'}'] = $answerText;

            $parts = [];
            $format = trim((string) ($question['output_format'] ?? ''));
            if ($format !== '') {
                $answerHtml = ($question['type'] ?? 'text') === 'text'
                    ? nl2br(e($answerText))
                    : e($answerText);
                $parts[] = str_replace('{answer}', $answerHtml, e($format));
            }

            $selected = is_array($answer) ? $answer : [$answer];
            foreach ($question['options'] ?? [] as $option) {
                $sentence = trim((string) ($option['sentence'] ?? ''));
                if ($sentence !== '' && in_array((string) ($option['label'] ?? ''), array_map('strval', $selected), true)) {
                    $parts[] = e($sentence);
                }
            }

            if ($parts !== []) {
                $paragraphs[] = '<p>'.implode(' ', $parts).'</p>';
            }
        }

        $title = (string) $template->title_template;
        $title = strtr($title, $titleReplacements);
        // 답변되지 않은 placeholder 제거
        $title = trim(preg_replace('/\{q\d{1,2}\}/', '', $title) ?? $title);
        $title = preg_replace('/\s{2,}/', ' ', $title) ?? $title;

        return [
            'title' => mb_substr($title !== '' ? $title : $template->name, 0, 160),
            'body_html' => implode("\n", $paragraphs),
        ];
    }

    private function isEmptyAnswer(mixed $raw): bool
    {
        if ($raw === null) {
            return true;
        }
        if (is_array($raw)) {
            return array_filter($raw, fn ($v) => trim((string) $v) !== '') === [];
        }

        return trim((string) $raw) === '';
    }
}
