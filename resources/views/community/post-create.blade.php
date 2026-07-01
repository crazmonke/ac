<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>새 글 작성</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --ink: #18283d;
            --muted: #607086;
            --line: #dde5ef;
            --brand: #2f52b8;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--bg); color: var(--ink); }
        .wrap { max-width: 740px; margin: 0 auto; padding: 12px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 14px; margin-bottom: 12px; }
        .meta { color: var(--muted); font-size: 0.88rem; }
        input, textarea { width: 100%; border: 1px solid #c7d8ea; border-radius: 14px; padding: 12px; font: inherit; background: #fff; }
        textarea { min-height: 180px; }
        button, a.btn { border: 0; border-radius: 999px; background: var(--brand); color: #fff; padding: 10px 14px; font-weight: 800; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        a.btn.secondary { background: #dde7f3; color: #20324b; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .grid { display: grid; gap: 10px; }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])
    <section class="card">
        <p class="meta"><a href="/community/{{ $board->slug }}?apartment_id={{ $apartmentId }}">← 목록으로</a></p>
        <h1 style="margin-top:0;">새 글 작성</h1>
        <p class="meta">게시판: {{ $board->name }}</p>

        <form method="post" enctype="multipart/form-data" action="/community/boards/{{ $board->slug }}/posts?apartment_id={{ $apartmentId }}">
            @csrf
            <div class="grid">
                <input name="title" placeholder="제목" value="{{ old('title') }}" required>
                <textarea name="body" placeholder="내용" required>{{ old('body') }}</textarea>
                <label>노출 카테고리
                    <select name="audience_scope" style="margin-top:6px;">
                        <option value="region" @selected(old('audience_scope', 'region') === 'region')>동네 (비회원은 제목만, 로그인 회원은 상세 가능)</option>
                        <option value="apartment" @selected(old('audience_scope') === 'apartment')>아파트 (같은 단지 인증 회원만 상세)</option>
                    </select>
                </label>
                <div class="meta" style="margin-top:-4px;">글쓰기는 인증회원만 가능합니다.</div>
                <label>태그/섹션 선택
                    <select name="post_topic_id" style="margin-top:6px;">
                        <option value="">선택 안 함</option>
                        @foreach($topicOptions as $topic)
                            <option value="{{ $topic->id }}" @selected((string) old('post_topic_id') === (string) $topic->id)>#{{ $topic->name }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="meta" style="margin-top:-4px;">기존 태그를 선택하거나, 아래에 새 태그를 입력하면 새로 생성됩니다.</div>
                <input name="new_topic" value="{{ old('new_topic') }}" placeholder="새 태그 만들기 (예: 반려동물, 리모델링, 육아)">
                @if($board->board_type === 'poll')
                    <label>투표 제목
                        <input name="poll_question" value="{{ old('poll_question') }}" style="margin-top:6px;" placeholder="예: 단지 회의 시간은 언제가 좋을까요?" required>
                    </label>
                    <label>투표 선택지
                        <textarea name="poll_options" style="margin-top:6px; min-height: 120px;" placeholder="선택지를 한 줄에 하나씩 입력하세요.&#10;오전 10시&#10;오후 2시&#10;오후 8시" required>{{ old('poll_options') }}</textarea>
                    </label>
                    <label><input type="checkbox" name="poll_allow_multiple" value="1" style="width:auto;" @checked(old('poll_allow_multiple'))> 복수 선택 허용</label>
                    <label><input type="checkbox" name="poll_results_public" value="1" style="width:auto;" @checked(old('poll_results_public', true))> 투표 결과 공개</label>
                @endif
                <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;" @checked(old('is_anonymous'))> 익명</label>
                <label><input type="checkbox" name="is_guest_visible" value="1" style="width:auto;" @checked(old('is_guest_visible'))> 비회원에게 본문 공개</label>
                <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf">
                <div class="actions">
                    <button type="submit">등록</button>
                    <a class="btn secondary" href="/community/{{ $board->slug }}?apartment_id={{ $apartmentId }}">취소</a>
                </div>
            </div>
        </form>
    </section>
</div>
</body>
</html>
