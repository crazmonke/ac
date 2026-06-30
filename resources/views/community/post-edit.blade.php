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
                <input name="title" value="{{ $post->title }}" required>
                <textarea name="body" required>{{ $post->body }}</textarea>
                <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;" @checked($post->is_anonymous)> 익명</label>
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
