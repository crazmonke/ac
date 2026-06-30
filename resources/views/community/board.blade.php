<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $board->name }}</title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f4f8fb; color: #17263d; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 24px; }
        .panel { background: #fff; border: 1px solid #d5dfec; border-radius: 12px; padding: 14px; margin-bottom: 14px; }
        .meta { color: #5b6d82; font-size: 0.9rem; }
        .flash { margin-bottom: 10px; padding: 10px; border-radius: 8px; border: 1px solid #bee6d9; background: #e8f6f1; color: #166b53; }
        .err { margin-bottom: 10px; padding: 10px; border-radius: 8px; border: 1px solid #f4c8c8; background: #fdecec; color: #9e1d1d; }
        .grid { display: grid; gap: 8px; grid-template-columns: repeat(2, minmax(120px, 1fr)); }
        input, textarea, select { width: 100%; border: 1px solid #c7d8ea; border-radius: 8px; padding: 9px; }
        textarea { min-height: 90px; }
        button { border: 0; border-radius: 8px; background: #0f6f67; color: #fff; padding: 8px 12px; font-weight: 700; cursor: pointer; }
        a { color: #0f6f67; text-decoration: none; font-weight: 700; }
        .list { margin-top: 12px; }
        .item { background: #fff; border: 1px solid #d5dfec; border-radius: 10px; padding: 12px; margin-bottom: 8px; }
        .item h3 { margin: 0 0 6px; }
        .pill { display: inline-block; border: 1px solid #c9d8eb; border-radius: 999px; padding: 2px 8px; font-size: 12px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>{{ $board->name }}</h1>
    <p class="meta">slug={{ $board->slug }} · apartment_id={{ $apartmentId }} · <a href="/community?apartment_id={{ $apartmentId }}">커뮤니티 홈</a></p>

    @if(session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    <section class="panel">
        <form method="get" action="/community/{{ $board->slug }}">
            <input type="hidden" name="apartment_id" value="{{ $apartmentId }}">
            <div class="grid">
                <div>
                    <input name="q" placeholder="검색어(제목/본문)" value="{{ $q }}">
                </div>
                <div>
                    <select name="sort">
                        <option value="latest" @selected($sort === 'latest')>최신순</option>
                        <option value="oldest" @selected($sort === 'oldest')>오래된순</option>
                        <option value="views" @selected($sort === 'views')>조회수순</option>
                        <option value="comments" @selected($sort === 'comments')>댓글순</option>
                    </select>
                </div>
                <div>
                    <button type="submit">검색/정렬 적용</button>
                </div>
            </div>
        </form>
    </section>

    @if($canWrite)
        <section class="panel">
            <h2>새 글 작성</h2>
            <form method="post" enctype="multipart/form-data" action="/community/boards/{{ $board->slug }}/posts?apartment_id={{ $apartmentId }}">
                @csrf
                <div class="grid">
                    <div style="grid-column: 1 / -1;">
                        <input name="title" placeholder="제목" required>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <textarea name="body" placeholder="내용" required></textarea>
                    </div>
                    <div>
                        <label><input type="checkbox" name="is_anonymous" value="1" style="width:auto;"> 익명</label>
                    </div>
                    <div>
                        <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.pdf">
                    </div>
                    <div>
                        <button type="submit">등록</button>
                    </div>
                </div>
            </form>
        </section>
    @endif

    <section class="list">
        @forelse($posts as $post)
            <article class="item">
                <h3>
                    <a href="/community/posts/{{ $post->id }}?apartment_id={{ $apartmentId }}">{{ $post->title }}</a>
                    @if($post->is_notice)
                        <span class="pill">공지</span>
                    @endif
                </h3>
                <div class="meta">
                    작성자: {{ $post->is_anonymous ? '익명' : ($post->user->name ?? '알 수 없음') }}
                    · 댓글 {{ $post->comment_count }}
                    · 조회 {{ $post->view_count }}
                    · {{ $post->created_at }}
                </div>
                <p>{{ \Illuminate\Support\Str::limit($post->body, 140) }}</p>
            </article>
        @empty
            <div class="item">게시글이 없습니다.</div>
        @endforelse
    </section>

    <div class="meta">{{ $posts->links() }}</div>
</div>
</body>
</html>
