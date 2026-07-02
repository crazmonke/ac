<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>댓글 수정</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border: 1px solid #d5dfec; border-radius: 12px; padding: 14px; margin-bottom: 14px; }
        input, textarea { width: 100%; border: 1px solid #c7d8ea; border-radius: 8px; padding: 9px; }
        textarea { min-height: 120px; }
        button, a.btn { border: 0; border-radius: 8px; background: #0f6f67; color: #fff; padding: 8px 12px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        a.btn.secondary { background: #dde7f3; color: #20324b; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .meta { color: #5b6d82; font-size: 0.9rem; }
        .back-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 999px;
            border: 1px solid #cfd8e6;
            background: #e9eef5;
            color: #22344d;
            padding: 8px 14px;
            font-size: 0.9rem;
            font-weight: 800;
            text-decoration: none;
            line-height: 1;
            transition: background-color 0.16s ease, border-color 0.16s ease;
        }
        .back-chip:hover { background: #dfe7f2; border-color: #c4d0e2; }
        .back-chip:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(47, 82, 184, 0.14); }
    </style>
</head>
<body>
<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])
    <p class="meta"><a class="back-chip" href="/community/posts/{{ $post->id }}?apartment_id={{ $apartmentId }}">← 게시글로 돌아가기</a></p>

    <section class="panel">
        <h1>댓글 수정</h1>
        <p class="meta">게시글: {{ $post->title }}</p>
        <form method="post" action="/community/comments/{{ $comment->id }}">
            @csrf
            @method('PUT')
            <div style="display:grid; gap:10px;">
                <textarea name="body" required>{{ $comment->body }}</textarea>
                <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;" @checked($comment->is_anonymous)> 익명</label>
                <div class="actions">
                    <button type="submit">수정 저장</button>
                    <a class="btn secondary" href="/community/posts/{{ $post->id }}?apartment_id={{ $apartmentId }}">취소</a>
                </div>
            </div>
        </form>
    </section>
</div>
</body>
</html>
