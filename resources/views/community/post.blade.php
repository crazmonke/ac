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
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--bg); color: var(--ink); }
        .wrap { max-width: 740px; margin: 0 auto; padding: 12px 12px 112px; }
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
        .appbar .title { font-weight: 800; font-size: 0.98rem; }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 14px;
            box-shadow: 0 10px 24px rgba(20, 35, 60, 0.04);
            margin-bottom: 12px;
        }
        .meta { color: var(--muted); font-size: 0.88rem; }
        .post-head { display: grid; gap: 12px; }
        .post-title { margin: 0; font-size: clamp(1.42rem, 4vw, 2rem); line-height: 1.28; }
        .author-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .author { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .avatar {
            width: 38px;
            height: 38px;
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
        .author-name { font-weight: 800; }
        .stats { display: flex; gap: 8px; flex-wrap: wrap; }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border-radius: 999px;
            padding: 5px 9px;
            font-size: 0.8rem;
            background: var(--brand-soft);
            color: var(--brand);
            font-weight: 700;
        }
        .body {
            white-space: pre-wrap;
            line-height: 1.75;
            font-size: 1rem;
            color: #1d2c42;
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
        .flash { margin-bottom: 10px; padding: 10px; border-radius: 10px; border: 1px solid #bee6d9; background: #e8f6f1; color: #166b53; }
        .err { margin-bottom: 10px; padding: 10px; border-radius: 10px; border: 1px solid #f4c8c8; background: #fdecec; color: #9e1d1d; }
        input, textarea { width: 100%; border: 1px solid #c7d8ea; border-radius: 14px; padding: 12px; font: inherit; background: #fff; }
        textarea { min-height: 110px; }
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
        }
        .danger { background: var(--danger); }
        .ghost { background: #e9eef7; color: #23334b; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .post-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .comment { display: grid; grid-template-columns: 36px 1fr; gap: 10px; padding: 14px 0; border-top: 1px solid #edf1f7; }
        .comment:first-child { border-top: 0; }
        .comment-body { min-width: 0; overflow: hidden; }
        .comment-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .comment-name { font-weight: 800; }
        .comment-meta { color: var(--muted); font-size: 0.8rem; margin-top: 2px; }
        .comment-text {
            margin-top: 8px;
            line-height: 1.65;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .comment-tools { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
        .comment-tools a, .comment-tools button {
            border-radius: 999px;
            padding: 7px 10px;
            font-size: 0.86rem;
        }
        .comment-tools a { text-decoration: none; }
        .best-box { background: #f7f9ff; border: 1px solid #dbe5ff; border-radius: 16px; padding: 12px; margin-bottom: 12px; }
        .best-label { display: inline-flex; align-items: center; gap: 6px; color: #2f52b8; font-weight: 800; font-size: 0.88rem; margin-bottom: 8px; }
        .children {
            margin-top: 12px;
            margin-left: 16px;
            padding-left: 14px;
            border-left: 3px solid #d9e4ff;
        }
        .reply-box {
            margin-top: 10px;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #e3eaf5;
            background: #f9fbff;
        }
        .reply-box textarea { min-height: 84px; }
        .attachment-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 8px; }
        .attachment-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            border: 1px solid #e5ebf4;
            border-radius: 14px;
            background: #fafcff;
        }
        .attachment-list a { color: var(--ink); text-decoration: none; font-weight: 700; }
        .composer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 25;
            background: rgba(245,247,251,0.96);
            border-top: 1px solid var(--line);
            backdrop-filter: blur(10px);
        }
        .composer-inner {
            max-width: 740px;
            margin: 0 auto;
            padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
        }
        .composer-bar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
            align-items: end;
        }
        .composer-bar textarea { min-height: 58px; max-height: 120px; }
        .composer-hint { color: var(--muted); font-size: 0.82rem; margin-top: 6px; }
        .bottom-bar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 30;
            background: rgba(255,255,255,0.98);
            border-top: 1px solid var(--line);
            backdrop-filter: blur(10px);
        }
        .bottom-bar-inner {
            max-width: 740px;
            margin: 0 auto;
            padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .bottom-bar a, .bottom-bar button {
            border: 0;
            border-radius: 999px;
            padding: 10px 12px;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .bottom-bar .primary { background: var(--brand); color: #fff; }
        .bottom-bar .ghost { background: #eef2f8; color: #24364e; }
        .bottom-bar .danger { background: var(--danger); color: #fff; }
        @media (min-width: 900px) {
            .wrap { max-width: 860px; padding-top: 18px; }
            .composer-inner { max-width: 860px; }
            .bottom-bar-inner { max-width: 860px; }
        }
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
            <a href="/community/{{ $post->board->slug }}?apartment_id={{ $apartmentId }}">← 목록</a>
            <div class="title">{{ $post->board->name }}</div>
        </div>
        <div class="right">
            <a href="/?apartment_id={{ $apartmentId }}">홈</a>
        </div>
    </div>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    <section class="card">
        <div class="post-head">
            <div class="meta">{{ $post->apartment->name }} · {{ $post->board->name }}</div>
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
                <div class="stats">
                    <span class="pill">조회 {{ $post->view_count }}</span>
                    <span class="pill">댓글 {{ $totalCommentCount }}</span>
                </div>
            </div>
        </div>

        <div style="margin-top:16px;" class="body">{{ $post->body }}</div>

        <div class="actions">
            @if($canWrite && ($currentUserId === $post->user_id || $isApartmentAdmin))
                <a class="btn" href="/community/posts/{{ $post->id }}/edit">수정</a>
                <form method="post" action="/community/posts/{{ $post->id }}" onsubmit="return confirm('삭제할까요?')" style="display:inline; margin:0;">
                    @csrf
                    @method('DELETE')
                    <button class="danger" type="submit">삭제</button>
                </form>
            @endif
        </div>
    </section>

    <section class="card">
        <div class="section-title">
            <h2>첨부파일</h2>
        </div>
        <ul class="attachment-list">
            @forelse($post->files as $file)
                <li>
                    <div>
                        <a href="/community/files/{{ $file->id }}">{{ $file->original_name }}</a>
                        <div class="meta">{{ number_format($file->size) }} bytes</div>
                    </div>
                    @if($canWrite && ($currentUserId === $post->user_id || $isApartmentAdmin || $currentUserId === $file->user_id))
                        <form method="post" action="/community/files/{{ $file->id }}" style="display:inline; margin:0;">
                            @csrf
                            @method('DELETE')
                            <button class="ghost" type="submit">삭제</button>
                        </form>
                    @endif
                </li>
            @empty
                <li class="meta">첨부파일이 없습니다.</li>
            @endforelse
        </ul>
    </section>

    <section class="card">
        <div class="section-title">
            <h2>댓글</h2>
            <div class="count">총 {{ $totalCommentCount }}개 · 댓글 {{ $rootCommentCount }}개 · 답글 {{ $replyCount }}개</div>
        </div>

        @if(count($bestCommentIds))
            <div class="best-box">
                <div class="best-label">✨ BEST 댓글</div>
                @foreach($post->comments->whereIn('id', $bestCommentIds) as $bestComment)
                    <article class="comment" style="padding-top: 8px;">
                        @php($bestCommentAuthorName = $bestComment->is_anonymous ? '익명' : ($bestComment->user->name ?? '알 수 없음'))
                        <div class="avatar">{{ $avatarInitial($bestCommentAuthorName) }}</div>
                        <div class="comment-body">
                            <div class="comment-head">
                                <div class="comment-name">{{ $bestCommentAuthorName }}</div>
                                <div class="meta">답글 {{ $bestComment->children->count() }}개</div>
                            </div>
                            <div class="comment-text">{{ $bestComment->body }}</div>

                            @if($bestComment->children->count())
                                <details style="margin-top:10px;">
                                    <summary>답글 {{ $bestComment->children->count() }}개 보기</summary>
                                    <div class="children" style="margin-top:8px;">
                                        @foreach($bestComment->children as $child)
                                            <article class="comment" style="grid-template-columns: 32px 1fr; padding-top:8px;">
                                                @php($bestChildAuthorName = $child->is_anonymous ? '익명' : ($child->user->name ?? '알 수 없음'))
                                                <div class="avatar" style="width:32px; height:32px;">{{ $avatarInitial($bestChildAuthorName) }}</div>
                                                <div class="comment-body">
                                                    <div class="comment-head">
                                                        <div class="comment-name">{{ $bestChildAuthorName }}</div>
                                                        <div class="meta">{{ $child->created_at }}</div>
                                                    </div>
                                                    <div class="comment-text">{{ $child->body }}</div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                </details>
                            @endif

                            @if($canComment)
                                <details style="margin-top:10px;">
                                    <summary>답글쓰기</summary>
                                    <form method="post" action="/community/posts/{{ $post->id }}/comments" class="reply-box">
                                        @csrf
                                        <input type="hidden" name="parent_id" value="{{ $bestComment->id }}">
                                        <textarea name="body" placeholder="답글을 입력하세요" required></textarea>
                                        <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;"> 익명</label>
                                        <div style="margin-top:8px;">
                                            <button type="submit">등록</button>
                                        </div>
                                    </form>
                                </details>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @forelse($post->comments as $comment)
            @if(in_array($comment->id, $bestCommentIds, true))
                @continue
            @endif
            <article class="comment">
                @php($commentAuthorName = $comment->is_anonymous ? '익명' : ($comment->user->name ?? '알 수 없음'))
                <div class="avatar">{{ $avatarInitial($commentAuthorName) }}</div>
                <div class="comment-body">
                    <div class="comment-head">
                        <div class="comment-name">{{ $commentAuthorName }}</div>
                        <div class="meta">{{ $comment->created_at }}</div>
                    </div>
                    <div class="comment-text">{{ $comment->body }}</div>

                    <div class="comment-tools">
                        @if($canComment)
                            <details>
                                <summary>답글쓰기</summary>
                                <form method="post" action="/community/posts/{{ $post->id }}/comments" class="reply-box">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                    <textarea name="body" placeholder="답글" required></textarea>
                                    <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;"> 익명</label>
                                    <div style="margin-top:8px;">
                                        <button type="submit">등록</button>
                                    </div>
                                </form>
                            </details>
                        @endif

                        @if($canComment && ($currentUserId === $comment->user_id || $isApartmentAdmin))
                            <a href="/community/comments/{{ $comment->id }}/edit">수정</a>
                            <form method="post" action="/community/comments/{{ $comment->id }}" onsubmit="return confirm('댓글을 삭제할까요?')" style="display:inline; margin:0;">
                                @csrf
                                @method('DELETE')
                                <button class="danger" type="submit">삭제</button>
                            </form>
                        @endif
                    </div>

                    @if($comment->children->count())
                        <div class="children">
                            @foreach($comment->children as $child)
                                <article class="comment" style="grid-template-columns: 32px 1fr;">
                                    @php($childAuthorName = $child->is_anonymous ? '익명' : ($child->user->name ?? '알 수 없음'))
                                    <div class="avatar" style="width:32px; height:32px;">{{ $avatarInitial($childAuthorName) }}</div>
                                    <div class="comment-body">
                                        <div class="comment-head">
                                            <div class="comment-name">{{ $childAuthorName }}</div>
                                            <div class="meta">{{ $child->created_at }}</div>
                                        </div>
                                        <div class="comment-text">{{ $child->body }}</div>
                                        <div class="comment-tools">
                                            @if($canComment && ($currentUserId === $child->user_id || $isApartmentAdmin))
                                                <a href="/community/comments/{{ $child->id }}/edit">수정</a>
                                                <form method="post" action="/community/comments/{{ $child->id }}" onsubmit="return confirm('답글을 삭제할까요?')" style="display:inline; margin:0;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="danger" type="submit">삭제</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="meta">아직 댓글이 없습니다.</div>
        @endforelse
    </section>
</div>

@if($canComment)
    <div class="composer" id="comment-composer">
        <div class="composer-inner">
            <form method="post" action="/community/posts/{{ $post->id }}/comments">
                @csrf
                <div class="composer-bar">
                    <textarea name="body" placeholder="댓글을 남겨보세요" required></textarea>
                    <button type="submit">등록</button>
                </div>
                <label style="display:inline-flex; align-items:center; gap:6px; margin-top:8px;">
                    <input type="checkbox" name="is_anonymous" value="1" style="width:auto;"> 익명
                </label>
                <div class="composer-hint">댓글은 화면 하단에서 바로 남길 수 있습니다.</div>
            </form>
        </div>
    </div>
@endif

<div class="bottom-bar">
    <div class="bottom-bar-inner">
        <a class="ghost" href="/community/{{ $post->board->slug }}?apartment_id={{ $apartmentId }}">목록</a>
        <button class="ghost" type="button" id="shareButton">공유</button>
    </div>
</div>

<script>
(() => {
    const shareButton = document.getElementById('shareButton');
    if (!shareButton) return;

    const shareUrl = window.location.href;
    const shareText = @json($post->title);

    shareButton.addEventListener('click', async () => {
        try {
            if (navigator.share) {
                await navigator.share({ title: shareText, text: shareText, url: shareUrl });
                return;
            }

            await navigator.clipboard.writeText(shareUrl);
            shareButton.textContent = '링크 복사됨';
            setTimeout(() => { shareButton.textContent = '공유'; }, 1200);
        } catch (error) {
            try {
                await navigator.clipboard.writeText(shareUrl);
            } catch (copyError) {
                console.error(copyError);
            }
        }
    });
})();
</script>
</body>
</html>
