<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>게시글 수정</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 860px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border: 1px solid #d5dfec; border-radius: 12px; padding: 14px; margin-bottom: 14px; }
        input, textarea { width: 100%; border: 1px solid #c7d8ea; border-radius: 8px; padding: 9px; }
        textarea { min-height: 140px; }
        button, a.btn { border: 0; border-radius: 8px; background: #0f6f67; color: #fff; padding: 8px 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        a.btn.secondary { background: #dde7f3; color: #20324b; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .meta { color: #5b6d82; font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])
    <p class="meta"><a href="/community/posts/{{ $post->id }}?apartment_id={{ $apartmentId }}">← 상세로 돌아가기</a></p>

    <section class="panel">
        <h1>게시글 수정</h1>
        <form method="post" enctype="multipart/form-data" action="/community/posts/{{ $post->id }}">
            @csrf
            @method('PUT')
            <div style="display:grid; gap:10px;">
                <input name="title" value="{{ old('title', $post->title) }}" required>
                <textarea name="body" required>{{ old('body', $post->body) }}</textarea>
                <label>노출 카테고리
                    <select name="audience_scope" style="margin-top:6px;">
                        <option value="region" @selected(old('audience_scope', $post->audience_scope ?? 'region') === 'region')>동네 (비회원은 제목만, 로그인 회원은 상세 가능)</option>
                        <option value="apartment" @selected(old('audience_scope', $post->audience_scope ?? 'region') === 'apartment')>아파트 (같은 단지 인증 회원만 상세)</option>
                    </select>
                </label>
                <div class="meta" style="margin-top:-4px;">글쓰기는 인증회원만 가능합니다.</div>
                <label>태그/섹션 선택
                    <select name="post_topic_id" style="margin-top:6px;">
                        <option value="">선택 안 함</option>
                        @foreach($topicOptions as $topic)
                            <option value="{{ $topic->id }}" @selected((string) old('post_topic_id', $post->post_topic_id) === (string) $topic->id)>#{{ $topic->name }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="meta" style="margin-top:-4px;">새 태그를 입력하면 선택한 기존 태그보다 우선 적용됩니다.</div>
                <input name="new_topic" value="{{ old('new_topic') }}" placeholder="새 태그 만들기 (입력 시 선택값보다 우선)">
                @if($post->board->board_type === 'poll')
                    <label>투표 제목
                        <input name="poll_question" value="{{ old('poll_question', $post->poll->question ?? '') }}" style="margin-top:6px;" placeholder="예: 단지 회의 시간은 언제가 좋을까요?" required>
                    </label>
                    <label>투표 선택지
                        <textarea name="poll_options" style="margin-top:6px; min-height: 120px;" placeholder="선택지를 한 줄에 하나씩 입력하세요." required>{{ old('poll_options', isset($post->poll) ? $post->poll->options->pluck('label')->implode("\n") : '') }}</textarea>
                    </label>
                    <label><input type="checkbox" name="poll_allow_multiple" value="1" style="width:auto;" @checked(old('poll_allow_multiple', $post->poll->allow_multiple ?? false))> 복수 선택 허용</label>
                    <label><input type="checkbox" name="poll_results_public" value="1" style="width:auto;" @checked(old('poll_results_public', $post->poll->results_public ?? true))> 투표 결과 공개</label>
                @endif
                <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;" @checked(old('is_anonymous', $post->is_anonymous))> 익명</label>
                <label><input type="checkbox" name="is_guest_visible" value="1" style="width:auto;" @checked(old('is_guest_visible', $post->is_guest_visible))> 비회원에게 본문 공개</label>
                <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf">
                <div class="actions">
                    <button type="submit">수정 저장</button>
                    <a class="btn secondary" href="/community/posts/{{ $post->id }}?apartment_id={{ $apartmentId }}">취소</a>
                </div>
            </div>
        </form>
    </section>

    @if($post->files->count())
        <section class="panel">
            <h2>현재 첨부파일</h2>
            <ul>
                @foreach($post->files as $file)
                    <li>{{ $file->original_name }}</li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
</body>
</html>
