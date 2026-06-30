<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->title }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border: 1px solid #d5dfec; border-radius: 12px; padding: 14px; margin-bottom: 14px; }
        .meta { color: #5b6d82; font-size: 0.9rem; }
        .body { white-space: pre-wrap; line-height: 1.6; }
        .flash { margin-bottom: 10px; padding: 10px; border-radius: 8px; border: 1px solid #bee6d9; background: #e8f6f1; color: #166b53; }
        .err { margin-bottom: 10px; padding: 10px; border-radius: 8px; border: 1px solid #f4c8c8; background: #fdecec; color: #9e1d1d; }
        input, textarea { width: 100%; border: 1px solid #c7d8ea; border-radius: 8px; padding: 9px; }
        textarea { min-height: 90px; }
        button { border: 0; border-radius: 8px; background: #0f6f67; color: #fff; padding: 8px 12px; font-weight: 700; cursor: pointer; }
        .danger { background: #b42318; }
        a { color: #0f6f67; text-decoration: none; font-weight: 700; }
        .comment { border-top: 1px solid #e6edf6; padding: 10px 0; }
        .children { margin-left: 20px; border-left: 2px solid #e3ebf6; padding-left: 10px; margin-top: 8px; }
        .file-list li { margin-bottom: 6px; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
    </style>
</head>
<body>
<div class="wrap">
    <p class="meta"><a href="/community/{{ $post->board->slug }}?apartment_id={{ $apartmentId }}">게시판으로 돌아가기</a></p>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    <section class="panel">
        <h1>{{ $post->title }}</h1>
        <p class="meta">
            작성자: {{ $post->is_anonymous ? '익명' : ($post->user->name ?? '알 수 없음') }}
            · 조회 {{ $post->view_count }}
            · {{ $post->created_at }}
        </p>
        <div class="body">{{ $post->body }}</div>

        <h3>첨부파일</h3>
        <ul class="file-list">
            @forelse($post->files as $file)
                <li>
                    <a href="/community/files/{{ $file->id }}">{{ $file->original_name }}</a>
                    ({{ number_format($file->size) }} bytes)
                    @if($canWrite && ($currentUserId === $post->user_id || $isApartmentAdmin || $currentUserId === $file->user_id))
                        <form method="post" action="/community/files/{{ $file->id }}" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="danger" type="submit">삭제</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="meta">첨부파일이 없습니다.</li>
            @endforelse
        </ul>
    </section>

    @if($canWrite && ($currentUserId === $post->user_id || $isApartmentAdmin))
        <section class="panel">
            <h2>게시글 수정</h2>
            <form method="post" enctype="multipart/form-data" action="/community/posts/{{ $post->id }}">
                @csrf
                @method('PUT')
                <input name="title" value="{{ $post->title }}" required>
                <textarea name="body" required>{{ $post->body }}</textarea>
                <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;" @checked($post->is_anonymous)> 익명</label>
                <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf">
                <div class="actions">
                    <button type="submit">수정</button>
                </div>
            </form>
            <form method="post" action="/community/posts/{{ $post->id }}" onsubmit="return confirm('삭제할까요?')" style="margin-top: 8px;">
                @csrf
                @method('DELETE')
                <button class="danger" type="submit">삭제</button>
            </form>
        </section>
    @endif

    <section class="panel">
        <h2>댓글 ({{ $post->comment_count }})</h2>

        @if($canComment)
            <form method="post" action="/community/posts/{{ $post->id }}/comments" style="margin-bottom: 12px;">
                @csrf
                <textarea name="body" placeholder="댓글을 입력하세요" required></textarea>
                <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;"> 익명</label>
                <button type="submit">댓글 등록</button>
            </form>
        @endif

        @forelse($post->comments as $comment)
            <article class="comment">
                <div class="meta">
                    {{ $comment->is_anonymous ? '익명' : ($comment->user->name ?? '알 수 없음') }}
                    · {{ $comment->created_at }}
                </div>
                <div class="body">{{ $comment->body }}</div>

                @if($canComment)
                    <details style="margin-top:6px;">
                        <summary>답글 작성</summary>
                        <form method="post" action="/community/posts/{{ $post->id }}/comments" style="margin-top:8px;">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                            <textarea name="body" placeholder="답글" required></textarea>
                            <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;"> 익명</label>
                            <button type="submit">답글 등록</button>
                        </form>
                    </details>
                @endif

                @if($canComment && ($currentUserId === $comment->user_id || $isApartmentAdmin))
                    <details style="margin-top:6px;">
                        <summary>댓글 수정</summary>
                        <form method="post" action="/community/comments/{{ $comment->id }}" style="margin-top:8px;">
                            @csrf
                            @method('PUT')
                            <textarea name="body" required>{{ $comment->body }}</textarea>
                            <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;" @checked($comment->is_anonymous)> 익명</label>
                            <button type="submit">수정 저장</button>
                        </form>
                    </details>

                    <form method="post" action="/community/comments/{{ $comment->id }}" onsubmit="return confirm('댓글을 삭제할까요?')" style="margin-top: 6px;">
                        @csrf
                        @method('DELETE')
                        <button class="danger" type="submit">삭제</button>
                    </form>
                @endif

                @if($comment->children->count())
                    <div class="children">
                        @foreach($comment->children as $child)
                            <article class="comment">
                                <div class="meta">
                                    {{ $child->is_anonymous ? '익명' : ($child->user->name ?? '알 수 없음') }}
                                    · {{ $child->created_at }}
                                </div>
                                <div class="body">{{ $child->body }}</div>

                                @if($canComment && ($currentUserId === $child->user_id || $isApartmentAdmin))
                                    <details style="margin-top:6px;">
                                        <summary>답글 수정</summary>
                                        <form method="post" action="/community/comments/{{ $child->id }}" style="margin-top:8px;">
                                            @csrf
                                            @method('PUT')
                                            <textarea name="body" required>{{ $child->body }}</textarea>
                                            <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;" @checked($child->is_anonymous)> 익명</label>
                                            <button type="submit">수정 저장</button>
                                        </form>
                                    </details>

                                    <form method="post" action="/community/comments/{{ $child->id }}" onsubmit="return confirm('답글을 삭제할까요?')" style="margin-top: 6px;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="danger" type="submit">삭제</button>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </article>
        @empty
            <div class="meta">아직 댓글이 없습니다.</div>
        @endforelse
    </section>
</div>
</body>
</html>
