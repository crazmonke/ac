<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->title }}</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --ink: #18283d;
            --muted: #607086;
            --line: #dde5ef;
            --brand: #2f52b8;
            --brand-soft: #ebf0ff;
            --danger: #b42318;
            --fixed-actions-height: calc(64px + env(safe-area-inset-bottom));
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--bg); color: var(--ink); }
        .wrap { max-width: 740px; margin: 0 auto; padding: 12px 12px calc(var(--fixed-actions-height) + 16px); }
        .appbar {
            position: sticky;
            top: 0;
            z-index: 15;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 4px 14px;
            background: linear-gradient(180deg, rgba(245,247,251,0.98), rgba(245,247,251,0.82));
            backdrop-filter: blur(8px);
        }
        .appbar .left,
        .appbar .right { display: flex; align-items: center; gap: 8px; }
        .appbar a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--ink);
            background: rgba(255,255,255,0.9);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 8px 11px;
            font-weight: 700;
            font-size: 0.92rem;
        }
        .appbar .back-chip {
            gap: 4px;
            background: #ffffff;
            border-color: #cfd8e6;
            color: #22344d;
            font-weight: 800;
            padding: 8px;
            line-height: 1;
        }
        .appbar .title { font-weight: 800; font-size: 0.98rem; }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 14px;
            box-shadow: 0 10px 24px rgba(20, 35, 60, 0.04);
            margin-bottom: 12px;
        }
        .card.highlight {
            background: #f9fbff;
            border-color: #d8e6ff;
            box-shadow: 0 10px 24px rgba(47, 82, 184, 0.08);
        }
        .meta { color: var(--muted); font-size: 0.88rem; }
        .post-head { display: grid; gap: 12px; }
        .post-title { margin: 0; font-size: clamp(1.42rem, 4vw, 2rem); line-height: 1.28; }
        .author-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .author { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .avatar {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            background: linear-gradient(145deg, #dce6ff, #eef2ff);
            border: 1px solid var(--line);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #35528a;
            flex: 0 0 auto;
        }
        .avatar.small {
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
        }
        .author-name { font-weight: 800; }
        .body {
            line-height: 1.75;
            font-size: 1rem;
            color: #1d2c42;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .body p,
        .body ul,
        .body ol,
        .body blockquote,
        .body pre {
            margin: 0 0 1em;
        }
        .body a {
            color: #1f4ca1;
            text-decoration: underline;
        }
        .body img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
        }
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .post-like-center {
            margin-top: 16px;
            display: flex;
            justify-content: center;
        }
        .like-toggle-form { display: inline-flex; }
        .like-toggle-btn {
            border: 1px solid #d7e1ee;
            background: #fff;
            color: #24364e;
            border-radius: 999px;
            padding: 8px 14px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 800;
            cursor: pointer;
        }
        .like-toggle-btn svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .like-toggle-btn.hearted {
            color: #d01e39;
            border-color: #efc0c8;
            background: #fff6f8;
        }
        .like-toggle-btn.hearted svg {
            fill: currentColor;
            stroke: currentColor;
        }
        .comment { display: grid; grid-template-columns: 32px 1fr; gap: 10px; padding: 12px 0; }
        .comment:first-child { padding-top: 0; }
        .comment-body { min-width: 0; overflow: hidden; }
        .comment-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .comment-name { font-weight: 800; font-size: 0.95rem; }
        .comment-meta { color: var(--muted); font-size: 0.8rem; }
        .comment-text {
            margin-top: 8px;
            line-height: 1.65;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
            font-size: 0.95rem;
        }
        .comment-tools { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .comment-tools a, .comment-tools button {
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 0.8rem;
            background: #e9eef7;
            color: #23334b;
            border: 0;
            cursor: pointer;
            text-decoration: none;
            font-weight: 700;
        }
        .comment-tools a:hover, .comment-tools button:hover {
            background: #dde5ef;
        }
        /* Threads 스타일 액션 */
        .comment-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: none;
            border: 0;
            padding: 0;
            color: var(--muted);
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border-radius: 0;
            line-height: 1;
        }
        .action-btn svg {
            width: 17px;
            height: 17px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }
        .action-btn.hearted { color: #d01e39; }
        .action-btn.hearted svg { fill: #d01e39; stroke: #d01e39; }
        .action-count { min-width: 12px; }
        .action-text { font-size: 0.8rem; color: var(--muted); }
        .danger-text { color: #b42318; }
        .children {
            margin-top: 12px;
            margin-left: 20px;
            padding-left: 12px;
            border-left: 2px solid #d9e4ff;
        }
        .reply-box {
            margin-top: 12px;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #e3eaf5;
            background: #f9fbff;
        }
        .reply-box textarea { 
            width: 100%; 
            border: 1px solid #c7d8ea; 
            border-radius: 12px; 
            padding: 10px; 
            font: inherit; 
            background: #fff;
            min-height: 80px;
            resize: vertical;
        }
        .reply-box label { 
            display: flex; 
            gap: 6px; 
            align-items: center; 
            margin-top: 8px; 
            font-size: 0.9rem;
        }
        .reply-box input { 
            width: auto; 
            margin: 0; 
        }
        button, .btn {
            border: 0;
            border-radius: 999px;
            background: var(--brand);
            color: #fff;
            padding: 10px 14px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }
        .danger { background: var(--danger); }
        .ghost { background: #e9eef7; color: #23334b; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .post-head { display: grid; gap: 12px; }
        .post-title { margin: 0; font-size: clamp(1.2rem, 3.5vw, 1.7rem); line-height: 1.28; }
        .author-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .author { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .author-name { font-weight: 800; }
        .post-like-center {
            margin-top: 16px;
            display: flex;
            justify-content: center;
        }
        .like-toggle-form { display: inline-flex; }
        .like-toggle-btn {
            border: 1px solid #d7e1ee;
            background: #fff;
            color: #24364e;
            border-radius: 999px;
            padding: 8px 14px;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 800;
            cursor: pointer;
        }
        .like-toggle-btn svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .like-toggle-btn.hearted {
            color: #d01e39;
            border-color: #efc0c8;
            background: #fff6f8;
        }
        .like-toggle-btn.hearted svg {
            fill: currentColor;
            stroke: currentColor;
        }
        .section-title {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 10px;
            margin: 0 0 10px;
        }
        .section-title h2 { margin: 0; font-size: 1.03rem; }
        .section-title .count { color: var(--muted); font-size: 0.88rem; }
    </style>
</head>
<body>
@php
    $avatarInitial = static function (?string $name): string {
        $value = trim((string) $name);
        if ($value === '') {
            return 'U';
        }
        return mb_strtoupper(mb_substr($value, 0, 1));
    };
@endphp

<div class="wrap">
    @include('partials.site-nav', ['apartmentId' => $apartmentId])

    <div class="appbar">
        <div class="left">
            <a class="back-chip" href="#" onclick="navigateBack(event);" data-apartment-id="{{ $apartmentId }}" data-post-id="{{ $post->id }}">  < </a>
            <div class="title">{{ $post->board->name }}</div>
        </div>
        <div class="right">
            <a href="/?apartment_id={{ $apartmentId }}">홈</a>
        </div>
    </div>

    @if(session('status'))
        <div class="flash" style="margin-bottom: 10px; padding: 10px; border-radius: 10px; border: 1px solid #bee6d9; background: #e8f6f1; color: #166b53;">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="err" style="margin-bottom: 10px; padding: 10px; border-radius: 10px; border: 1px solid #f4c8c8; background: #fdecec; color: #9e1d1d;">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- 원글 전체 -->
    <section class="card" style="border-bottom: 3px solid var(--line);">
        <div class="post-head">
            <div class="meta">{{ $post->audience_scope === 'apartment' ? ($post->apartment->name.' · ') : '동네전용 · ' }}{{ $post->board->name }}</div>
            <h1 class="post-title">{{ $post->title }}</h1>
            <div class="author-row">
                <div class="author">
                    @php($postAuthorName = $post->is_anonymous ? '익명' : ($post->user->name ?? '알 수 없음'))
                    <div class="avatar">{{ $avatarInitial($postAuthorName) }}</div>
                    <div>
                        <div class="author-name">{{ $postAuthorName }}</div>
                        <div class="meta">{{ $post->created_at }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top:16px;" class="body">{!! $post->body !!}</div>

        @if($post->files && $post->files->count())
            <ul style="list-style:none; margin:12px 0 0; padding:0; display:grid; gap:8px;">
                @foreach($post->files as $file)
                    @if(in_array($file->mime_type ?? '', ['image/jpeg','image/png','image/gif','image/webp']))
                        <li><img src="{{ $file->url ?? '/community/files/'.$file->id }}" alt="" style="max-width:100%; border-radius:10px;"></li>
                    @else
                        <li style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border:1px solid #e5ebf4; border-radius:14px; background:#fafcff;">
                            <a href="/community/files/{{ $file->id }}" style="color:var(--ink); text-decoration:none; font-weight:700;">{{ $file->original_name ?? $file->filename }}</a>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif

        <div class="post-like-center">
            <form method="post" action="/community/posts/{{ $post->id }}/likes" class="like-toggle-form" data-like-form data-liked="{{ $likedByMe ? '1' : '0' }}">
                @csrf
                @if($likedByMe)
                    @method('DELETE')
                @endif
                <button class="like-toggle-btn {{ $likedByMe ? 'hearted' : '' }}" type="submit" aria-label="좋아요">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.84 4.61a4.98 4.98 0 0 0-7.05 0L12 6.4l-1.79-1.79a4.98 4.98 0 0 0-7.05 7.05L12 20.5l8.84-8.84a4.98 4.98 0 0 0 0-7.05Z"/></svg>
                    <span data-like-count>{{ $likeCount }}</span>
                </button>
            </form>
        </div>
    </section>

    <!-- 클릭한 댓글 (강조) -->
    <section class="card highlight">
        <div class="section-title">
            <h2>댓글</h2>
        </div>

        <article class="comment">
            @php($commentAuthorName = $comment->is_anonymous ? '익명' : ($comment->user->name ?? '알 수 없음'))
            <div class="avatar">{{ $avatarInitial($commentAuthorName) }}</div>
            <div class="comment-body">
                <div class="comment-head">
                    <div>
                        <div class="comment-name">{{ $commentAuthorName }}</div>
                        <div class="comment-meta">{{ format_relative_time($comment->created_at) }}</div>
                    </div>
                </div>
                <div class="comment-text">{{ $comment->body }}</div>

                <div class="comment-actions">
                    @php($commentLiked = isset($myCommentLikes[$comment->id]))
                    @php($commentLikeCount = (int)($commentLikeCounts[$comment->id] ?? 0))
                    <form method="post" action="/community/comments/{{ $comment->id }}/likes"
                          class="c-like-form" data-like-form-comment
                          data-liked="{{ $commentLiked ? '1' : '0' }}">
                        @csrf
                        @if($commentLiked) @method('DELETE') @endif
                        <button type="submit" class="action-btn {{ $commentLiked ? 'hearted' : '' }}" aria-label="좋아요">
                            <svg viewBox="0 0 24 24"><path d="M20.84 4.61a4.98 4.98 0 0 0-7.05 0L12 6.4l-1.79-1.79a4.98 4.98 0 0 0-7.05 7.05L12 20.5l8.84-8.84a4.98 4.98 0 0 0 0-7.05Z"/></svg>
                            <span class="action-count" data-like-count>{{ $commentLikeCount ?: '' }}</span>
                        </button>
                    </form>
                    @if($canComment)
                        <button type="button" class="action-btn" onclick="document.getElementById('replyForm').scrollIntoView({behavior:'smooth'})" aria-label="답글쓰기">
                            <svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
                            <span class="action-count">{{ $comment->children->count() ?: '' }}</span>
                        </button>
                    @endif
                    @if($canComment && ($currentUserId === $comment->user_id || $isApartmentAdmin))
                        <a href="/community/comments/{{ $comment->id }}/edit" class="action-btn action-text">수정</a>
                        <form method="post" action="/community/comments/{{ $comment->id }}" onsubmit="return confirm('댓글을 삭제할까요?')" style="display:inline; margin:0;">
                            @csrf @method('DELETE')
                            <button type="submit" class="action-btn action-text danger-text">삭제</button>
                        </form>
                    @endif
                </div>
            </div>
        </article>
    </section>

    <!-- 답글들 -->
    @if($comment->children->count())
        <section class="card">
            <div class="children">
                @foreach($comment->children as $child)
                    <article class="comment">
                        @php($childAuthorName = $child->is_anonymous ? '익명' : ($child->user->name ?? '알 수 없음'))
                        <div class="avatar small">{{ $avatarInitial($childAuthorName) }}</div>
                        <div class="comment-body">
                            <div class="comment-head">
                                <div>
                                    <div class="comment-name">{{ $childAuthorName }}</div>
                                    <div class="comment-meta">{{ format_relative_time($child->created_at) }}</div>
                                </div>
                            </div>
                            <div class="comment-text">{{ $child->body }}</div>

                            <div class="comment-actions">
                                @php($childLiked = isset($myCommentLikes[$child->id]))
                                @php($childLikeCount = (int)($commentLikeCounts[$child->id] ?? 0))
                                <form method="post" action="/community/comments/{{ $child->id }}/likes"
                                      class="c-like-form" data-like-form-comment
                                      data-liked="{{ $childLiked ? '1' : '0' }}">
                                    @csrf
                                    @if($childLiked) @method('DELETE') @endif
                                    <button type="submit" class="action-btn {{ $childLiked ? 'hearted' : '' }}" aria-label="좋아요">
                                        <svg viewBox="0 0 24 24"><path d="M20.84 4.61a4.98 4.98 0 0 0-7.05 0L12 6.4l-1.79-1.79a4.98 4.98 0 0 0-7.05 7.05L12 20.5l8.84-8.84a4.98 4.98 0 0 0 0-7.05Z"/></svg>
                                        <span class="action-count" data-like-count>{{ $childLikeCount ?: '' }}</span>
                                    </button>
                                </form>
                                @if($canComment && ($currentUserId === $child->user_id || $isApartmentAdmin))
                                    <a href="/community/comments/{{ $child->id }}/edit" class="action-btn action-text">수정</a>
                                    <form method="post" action="/community/comments/{{ $child->id }}" onsubmit="return confirm('답글을 삭제할까요?')" style="display:inline; margin:0;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="action-btn action-text danger-text">삭제</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <!-- 답글 작성 폼 -->
    @if($canComment)
        <section class="card" id="replyForm">
            <div class="section-title">
                <h2>답글 작성</h2>
            </div>
            <form method="post" action="/community/posts/{{ $post->id }}/comments" class="reply-box" style="margin-top: 0;">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                <textarea name="body" placeholder="답글을 입력하세요" required></textarea>
                <label>
                    <input type="checkbox" name="is_anonymous" value="1"> 익명
                </label>
                <div style="margin-top: 10px;">
                    <button type="submit">등록</button>
                </div>
            </form>
        </section>
    @endif
</div>

<script>
(() => {
    // 좋아요(게시물 + 댓글) AJAX 공통 처리
    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-like-form], form[data-like-form-comment]');
        if (!form) return;
        event.preventDefault();
        if (form.dataset.loading === '1') return;
        form.dataset.loading = '1';

        const button = form.querySelector('button[type="submit"]');
        const methodInput = form.querySelector('input[name="_method"]');
        const prevLiked = form.dataset.liked === '1';
        if (button) button.disabled = true;

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error();
            const data = await res.json();
            const liked = Boolean(data.liked);
            const count = Number(data.like_count ?? 0);
            form.dataset.liked = liked ? '1' : '0';
            if (button) {
                button.classList.toggle('hearted', liked);
                const span = button.querySelector('[data-like-count]');
                if (span) span.textContent = count || '';
            }
            if (liked && !methodInput) {
                const h = document.createElement('input');
                h.type = 'hidden'; h.name = '_method'; h.value = 'delete';
                form.appendChild(h);
            }
            if (!liked && methodInput) methodInput.remove();
        } catch {
            form.dataset.liked = prevLiked ? '1' : '0';
            alert('좋아요 처리에 실패했습니다.');
        } finally {
            if (button) button.disabled = false;
            form.dataset.loading = '0';
        }
    });

    // 뒤로가기 버튼 처리
    window.navigateBack = function(event) {
        event.preventDefault();
        const referrer = document.referrer;
        const backChip = event.target.closest('.back-chip');
        const apartmentId = backChip?.getAttribute('data-apartment-id');
        const postId = backChip?.getAttribute('data-post-id');
        if (referrer && postId && referrer.includes('/community/posts/' + postId)) {
            history.back();
        } else {
            window.location.href = `/community/posts/${postId}?apartment_id=${apartmentId}`;
        }
    };
})();
</script>
</body>
</html>
